<?php
namespace App\Controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/**
 * Description of ArticleController
 *
 * @author marwansaleh 4:04:41 PM
 */
class ArticleController extends Controller {
    private $_image_sizes = [IMAGE_THUMB_ORI, IMAGE_THUMB_LARGE, IMAGE_THUMB_PORTRAIT, IMAGE_THUMB_MEDIUM, IMAGE_THUMB_SMALL,
        IMAGE_THUMB_SQUARE, IMAGE_THUMB_SMALLER, IMAGE_THUMB_TINY];
    
    public function Index(Request $req, Response $res) {
        return $res->withJson(['services'=>['version'=>'1']]);
    }
    
    public function GetArticles(Request $req, Response $res) {
        $return = [];
        
        $query_string = $req->getQueryParams();
        $limit = isset($query_string['pageSize']) && (int) $query_string['pageSize']>0 ? $query_string['pageSize'] : 40; //default 40 articles
        $page = isset($query_string['page']) && (int) $query_string['page']>0 ? $query_string['page'] : 1; //default page 1
        $q = isset($query_string['q']) && !is_null($query_string['q']) ? $query_string['q']  : null;
        
        $offset = ($page - 1) * $limit;
        
        try {
            $sql = "SELECT id, category_id, title, url_title, url_short, FROM_UNIXTIME(date) date, day, month, year, synopsis, "
                    . "image_url, image_caption, image_type, tags, types, allow_comment, comment, view_count, "
                    . "FROM_UNIXTIME(created) created FROM {$this->_tb_article}";
            $sql.=" WHERE published=1";
            if ($q) {
                $sql.=" AND(title LIKE '%$q%' OR synopsis LIKE '%$q%')";
            }
            $sql.= "  ORDER BY created desc LIMIT $offset,$limit";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $articles = $stmt->fetchAll(\PDO::FETCH_OBJ);
            
            $clean_utf = [];
            foreach ($articles as $article) {
                $article->title = mb_convert_encoding($article->title, 'UTF-8', 'UTF-8');
                $article->synopsis = mb_convert_encoding($article->synopsis, 'UTF-8', 'UTF-8');
                
                //Images
                $image_name = $article->image_url;
                $article->image_url = [];
                foreach ($this->_image_sizes as $i_size){
                    $article->image_url[] [$i_size] = $this->helper->get_image_url($image_name, $i_size);
                }
                
                $clean_utf[] = $article;
            }
            $return['status'] = true;
            $return['totalResults'] = count($articles);
            if ($q){
                $return['q'] = $q;
            }
            $return['articles'] = $clean_utf;
            
        } catch (\PDOException $ex) {
            $return['status'] = FALSE;
            $return['exceptionCode'] = $ex->getCode();
            $return['exceptionMessage'] = $ex->getMessage();
        }
        
        return $res->withJson($return);
    }
    
    public function GetArticle(Request $req, Response $res) {
        $return = [];
        $id = $req->getAttribute('id');
        
        $query_string = $req->getQueryParams();
        $limit_related = isset($query_string['related']) && (int) $query_string['related']>0 ? $query_string['related'] : 10;
        
        $limit_related++; //later will exclude same id
        
        try {
            $sql = "SELECT A.id, A.category_id, C.name category_name, C.parent category_parent, A.title, A.url_title, "
                    . "A.url_short, FROM_UNIXTIME(A.date) date, A.day, A.month, A.year, A.synopsis, A.content, "
                    . "A.image_url, A.image_caption, A.image_type, A.tags, A.types, A.allow_comment, A.comment, A.view_count, "
                    . "FROM_UNIXTIME(A.modified) modified, FROM_UNIXTIME(A.created) created, A.hide_author, "
                    . "A.created_by, U.full_name created_by_name, A.ext_attributes "
                    . "FROM {$this->_tb_article} A "
                    . "JOIN {$this->_tb_category} C ON C.id=A.category_id "
                    . "JOIN {$this->_tb_user} U ON U.id=A.created_by "
                    . "WHERE A.id=?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($id));
            $article = $stmt->fetch(\PDO::FETCH_OBJ);
            
            
            if ($article) {
                //clearing up
                $article->title = mb_convert_encoding($article->title, 'UTF-8', 'UTF-8');
                $article->synopsis = mb_convert_encoding($article->synopsis, 'UTF-8', 'UTF-8');
                $article->hide_author = $article->hide_author == 0 ? FALSE : TRUE;
                
                //Images
                $image_name = $article->image_url;
                $article->image_url = [];
                foreach ($this->_image_sizes as $i_size){
                    $article->image_url[] [$i_size] = $this->helper->get_image_url($image_name, $i_size);
                }
                
                // Describe the category
                $params_category_screening = "{$article->category_id}";
                if ($article->category_parent>0) {
                    $params_category_screening .= "," . $article->category_parent;
                }
                
                $sql = "SELECT id,name,slug,parent,is_menu,is_home,image_url FROM {$this->_tb_category} "
                    . "WHERE id IN ($params_category_screening)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $article->category_info = $stmt->fetchAll(\PDO::FETCH_OBJ);
                
                // Get antoher images
                if ($article->image_type == IMAGE_TYPE_MULTI) {
                    $article->multi_images = [];
                    
                    $sql = "SELECT image_url FROM {$this->_tb_image} WHERE article_id=?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute(array($article->id));
                    $multi_images = $stmt->fetchAll(\PDO::FETCH_OBJ);
                    
                    foreach ($multi_images as $m_img) {
                        $multi = [];
                        foreach ($this->_image_sizes as $i_size){
                            $multi[$i_size] = $this->helper->get_image_url($m_img->image_url, $i_size);
                        }
                        $article->multi_images[] = $multi;
                    }
                }
                
                // Get comments if any
                $sql = "SELECT ip_address, date, comment, article_id, is_approved FROM {$this->_tb_comment} "
                        . "WHERE article_id=? "
                        . "ORDER BY date desc";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array($article->id));
                $article->comments = $stmt->fetchAll(\PDO::FETCH_OBJ);
                
                // Get related articles based on tag
                if ($article->tags) {
                    $article_tags = [];
                    
                    foreach (explode(',', $article->tags) as $tag) {
                        $article_tags[] = "(tags LIKE '%$tag%')"; 
                    }
                    
                    $tag_sql_str = implode('OR', $article_tags);
                    $sql = "SELECT id, title, url_short, image_url, image_caption, viewed_count, comment, created, modified FROM {$this->_tb_article} "
                    . "WHERE published=1 AND ($tag_sql_str) "
                            . "ORDER BY created DESC LIMIT $limit_related";
                    
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute();
                    
                    // Return related articles exclude the article it self
                    $related_articles =[];
                    foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $r) {
                        if ($r->id != $article->id) {
                            $r->title = mb_convert_encoding($r->title, 'UTF-8', 'UTF-8');
                            $image_name = $r->image_url;
                            $r->image_url = [];
                            foreach ($this->_image_sizes as $i_size) {
                                $r->image_url [$i_size] = $this->helper->get_image_url($image_name, $i_size);
                            }
                            
                            $related_articles[] = $r;
                        }
                    }
                    
                    $article->related = $related_articles;
                } else {
                    $article->related = [];
                }
            }
            
            $return['status'] = true;
            $return['article'] = $article;
            
        } catch (\PDOException $ex) {
            $return['status'] = false;
            $return['exceptionCode'] = $ex->getCode();
            $return['exceptionMessage'] = $ex->getMessage();
        }
        return $res->withJson($return);
    }
    
    public function setIncrementView(Request $req, Response $res) {
        $return = [];
        $id = $req->getAttribute('id');
        
        $sql = "UPDATE {$this->_tb_article} SET viewed_count=viewed_count+1 WHERE id=?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($id));
            
            $return['status'] = true;
        } catch (\PDOException $ex) {
            $return['status'] = false;
            $return['exceptionCode'] = $ex->getCode();
            $return['exceptionMessage'] = $ex->getMessage();
        }
        
        return $res->withJson($return);
    }
}

/**
 * Filename : ArticleController.php
 * Location : /ArticleController.php
 */

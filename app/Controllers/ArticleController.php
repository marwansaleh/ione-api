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
            $sql = "SELECT id, category_id, title, url_title, url_short, date, day, month, year, synopsis, "
                    . "image_url, image_caption, image_type, tags, types, allow_comment, comment, view_count, "
                    . "modified, created FROM {$this->_tb_article}";
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
            $sql = "SELECT `P`.`id`, `P`.`edition`, `P`.`title`, `P`.`date`, `P`.`datetime`, `P`.`year`, "
                    . "`P`.`month`, `P`.`day`, `P`.`global_category`, `P`.`category`, `C`.`category` category_name, `P`.`slug`, "
                    . "`P`.`author`, `U`.`full_name` author_name, `P`.`main_image`, `P`.`tag`, `P`.`synopsis`, `P`.`content`, "
                    . "`P`.`is_publishedYN`, `P`.`viewed`, `P`.`allowed_comment`, `P`.`comment`, "
                    . "`P`.`last_update`, `P`.`google_short_url` "
                    . "FROM {$this->_tb_article} P JOIN {$this->_tb_category} C ON `P`.`category`=`C`.`id` "
                    . "JOIN {$this->_tb_user} U ON `P`.`author`=`U`.`user_id` "
                    . "WHERE `P`.`id`=?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($id));
            $article = $stmt->fetch(\PDO::FETCH_OBJ);
            
            
            if ($article) {
                //Get comments if any
                
                $sql = "SELECT ip_address, date, comment, is_approved FROM {$this->_tb_comment} "
                . "WHERE article_id=? "
                        . "ORDER BY date desc";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array($article->id));
                $article->comments = $stmt->fetchAll(\PDO::FETCH_OBJ);
                
                //Get related articles based on tag
                if ($article->tag) {
                    $article_tags = [];
                    
                    foreach (explode(',', $article->tag) as $tag) {
                        $article_tags[] = "(tag LIKE '%$tag%')"; 
                    }
                    
                    $tag_sql_str = implode('OR', $article_tags);
                    $sql = "SELECT id, title, slug, main_image, viewed, comment, last_update FROM {$this->_tb_article} "
                    . "WHERE is_publishedYN='y' AND ($tag_sql_str) "
                            . "ORDER BY id DESC LIMIT $limit_related";
                    
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute();
                    
                    // Return related articles exclude the article it self
                    $related_articles =[];
                    foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $r) {
                        if ($r->id != $article->id) {
                            $r->title = mb_convert_encoding($r->title, 'UTF-8', 'UTF-8');
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
        
        $sql = "UPDATE {$this->_tb_article} SET viewed=viewed+1 WHERE id=?";
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

<?php

//API group
$app->group('/v1', function() use ($app){
    $app->get('/', function(\Slim\Http\Request $req, \Slim\Http\Response $res){
        return $res->withJson(['service'=>'1']);
    });
    $app->get('/articles', App\Controllers\ArticleController::class . ':GetArticles')->setName('GetArticleList');
    $app->get('/articles/{id}', App\Controllers\ArticleController::class . ':GetArticle')->setName('GetArticleById');
    $app->post('/articles/increase_view/{id}', App\Controllers\ArticleController::class .':setIncrementView')->setName('SetViewIncrement');
});

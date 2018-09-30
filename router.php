<?php

$router = $di->getRouter();

// Default Route
$router->add('/',['controller'=>'index','action'=>'index']);


//Normal Categorybook
$router->add('/detail-product/{catid}/{proid}',['controller'=>'index','action'=>'detailproduct']);

//Details Category
$router->add('/details/{id}',['controller'=>'index','action'=>'details']);



//Save comment
$router->add('/save',['controller'=>'index','action'=>'save']);

//Save comment
$router->add('/comment-delete/{commentid}',['controller'=>'index','action'=>'commentdelete']);


//Create account page
$router->add('/create-account',['controller'=>'account','action'=>'index']);

//Create account 
$router->add('/register',['controller'=>'account','action'=>'register']);

//login account 
$router->add('/login',['controller'=>'account','action'=>'login']);

//After login 
$router->add('/user-home',['controller'=>'account','action'=>'userhome']);


//Logout Action
$router->add('/logout',['controller'=>'account','action'=>'logout']);


//Change profile picture
$router->add('/change-pic',['controller'=>'account','action'=>'changepic']);
 
//profile save

$router->add('/profile-save',['controller'=>'account','action'=>'profilesave']);

//Export Activity Excel

$router->add('/export',['controller'=>'account','action'=>'csv']);

//Pdf generation

$router->add('/pdf-generate',['controller'=>'account','action'=>'pdfgenerate']);


//subscription
$router->add('/subscription',['controller'=>'account','action'=>'subscription']);


$router->handle();

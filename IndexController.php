<?php
use Phalcon\Paginator\Adapter\Model as PaginatorModel;
class IndexController extends ControllerBase
{

    public function indexAction()
    {
    
      $currentPage = (int) $_GET['page'];
      
      $title = "";
      $this->view->setVar('titlename',$title);
      $this->view->categorylist = Category::find(["totalitem > 0", "order" => "categoryname ASC"]);
      $this->view->bannerCount = Slider::count();
      $this->view->banner = Slider::find();
      
      //$this->view->data = Item::find(["order" => "name ASC"]);
      $data = Item::find(["order" => "name ASC"]);

      /*Pagination*/ 
      $paginator = new PaginatorModel(
          [
              'data'  => $data,
              'limit' => 15,
              'page'  => $currentPage,
          ]
      );
     $page = $paginator->getPaginate();
     $this->view->page = $page;
   
    }


public function detailsAction()
{
    //For getting current page for pagination
    $currentPage = (int) $_GET['page'];

      $id = $this->dispatcher->getParam('id');
      $this->view->cid = $id;
	  if($id)
	  {
	  	$this->view->categorySelected = Category::findfirst($id);
	  	$title = "";
	  	$this->view->categorylist = Category::find(["id != $id and totalitem > 0","order" =>"categoryname ASC"]);
	  	$this->view->setVar('titlename',$title);
	  }
	  else
	  {
	  	$title = "Category ";
	  	$this->view->setVar('titlename',$title);
	  	$this->view->categorylist = Category::find(["order" => "categoryname ASC"]);

	  }	
	  
      
      
      //$this->view->data = Item::find();
      $data_all = $this->modelsManager->createBuilder()
        
      ->addFrom('item')
      ->leftJoin('category', 'item.categoryid = category.id')
      ->where('category.id ='.$id)
      ->getQuery()
      ->execute(); 
      //$this->view->data = $data_all;

      /*Pagination */
      $paginator = new PaginatorModel(
          [
              'data'  => $data_all,
              'limit' => 9,
              'page'  => $currentPage,
          ]
      );
     $page = $paginator->getPaginate();
     $this->view->page = $page;



	  $this->view->pick('details/details');
}


public function detailproductAction()
{

$catid = $this->dispatcher->getParam('catid');
$proid = $this->dispatcher->getParam('proid');

	  if($catid)
	  {
	  	$categorySelected = Category::findfirst($catid);
	  	$catname = $categorySelected->categoryname;
	  	$this->view->categorylist = Category::find("id != $catid and totalitem > 0");
	  	$this->view->categorySelected = $categorySelected;
	  }

      
      
      $data = Item::findfirst("id = $proid");
      $productname = $data->name;
      
      $updatedtime = $data->updatedat;
      date_default_timezone_set("Asia/Kuala_Lumpur");
      $cureenttime = date("Y-m-d H:i:s");
      $ts1 = strtotime($updatedtime);
      $ts2 = strtotime($cureenttime);

      $interval = $ts2 - $ts1;
      //$this->view->interval = $cureenttime.'-'.$updatedtime.'='.$interval ;
      if($interval > 60)
      {
      $initial = $data->view;
      $after   = $initial +1 ;
      $res = $this->db->execute("UPDATE item SET view = ? WHERE id = ?",array($after,$proid));
      }
      
      $this->view->dataDetails=Item::findfirst("id = $proid");
      $title = "$catname/$productname";
      $this->view->setVar('titlename',$title);


      $this->view->comment = Comment::find(["productid = $proid","order" => "postedat DESC"]);
      




	$this->view->pick('detailproduct/detailproduct');
}




public function saveAction()
{
        $comment = new Comment();

        // Store and check for errors
        $success = $comment->save(
            $this->request->getPost(),
            [
                "productid",
                "username",
                "email",
                "comment"
            ]
        );

        if ($success) 
        {
            $this->flashSession->success('Success ::  Comment saved.');
        } 
        else 
        {
            //Error Message
            $this->flashSession->error("Error :: Please try again!");            
        }
        
       // $message = "My Name is Asad";
       
        $var1 = $this->request->getPost('catid');
        $var2 = $this->request->getPost('productid');
        
        // Make a full HTTP redirection
        return $this->response->redirect('detail-product/'.$var1.'/'.$var2,['message'=>$message]);


}



public function commentdeleteAction()
{
    $commentid = $this->dispatcher->getParam('commentid');  

    $itemid = Comment::findFirst($commentid);
    $id = $itemid->productid;

    $findroot = Item::findFirst("id = $id");
    $var1  = $findroot->categoryid;
    $var2  = $findroot->id;

    $res = $this->db->execute("DELETE FROM comment WHERE id = ?",array($commentid));
    if($res)
    {
    $this->flashSession->success("Delete :: Comment Deleted!");  
    }
    else
    {
    $this->flashSession->error("Fail :: Error!");
    }
    return $this->response->redirect('detail-product/'.$var1.'/'.$var2);
}



}


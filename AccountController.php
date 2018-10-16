<?php
require_once __DIR__ . '../../../vendor/autoload.php';
use Phalcon\Mvc\Controller;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;
use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\Model\Query\Builder;

use \Phalcon\Queue\Db as DbQueue;
use \Phalcon\Queue\Db\Job as Job;




class AccountController extends ControllerBase
{


    public function indexAction()
    {
            // This is root controller.

            //#...Check User already signed in 
            $sessions = $this->getDI()->getShared("session");
            if ($sessions->has("user_id")) {
                //if user is already logged then redirect
                return $this->response->redirect("user-home");
            }
            else
            {
            $this->view->pick('account/account');
            }  
    }



    public function registerAction()
    {

       $mail = new Mail();    



      
      if ($this->request->isPost()) {

            $name     = $this->request->getPost('name');
            $password = $this->request->getPost('userpassword');
            $confpassword = $this->request->getPost('confuserpassword');
            $email    = $this->request->getPost('useremail');
            $phone    = $this -> request -> getPost('phone');



            if(!is_numeric($phone))
            {
             $this->flashSession->error('Error :: Only Digit ,No Charecter');
             return $this->view->pick('account/account');
             //return false;
            }


            if ($password != $confpassword) 
            {
                $this->flashSession->error('Error :: Password should be same.');
                return $this->view->pick('account/account');
            }

            $check = Registration::count("email = '$email'");
            if(!empty($check))
            {
              $this->flashSession->error('Error :: Already Exist');
              return $this->view->pick('account/account');             
            }
            else
            {
            $register = new Registration();
            $register->name = $name;
            $register->password = sha1($password);
            $register->email = $email;
            $register->phone = $phone;
            $register->level = '1';
            $register->active = '1';
            $register->location = 'Dhaka';
            
              if($register->save() == false) 
               {
                
                    foreach ($register->getMessages() as $message) 
                    {
                    $this->flashSession->error((string) $message);

                    }
                    $this->view->pick('account/account');
              } 
              else 
              {

                 /**
                 * Send Email
                 */
                 $params = [
                     'name' => $this->request->getPost('name'),
                     'link' => "http://localhost/CarrierPhalcon/create-account",
                     'password' => $this->request->getPost('userpassword')
                 ];
                 $mail->send($this->request->getPost('useremail', ['trim', 'email']), 'signup', $params);
          
                $this->flashSession->success('Success :: Account Created.Registration email Sent.');
                return $this->response->redirect('create-account');
              }
            }  
        }




    }



    public function loginAction()
    {

            $sessions = $this->getDI()->getShared("session");

            if ($sessions->has("user_id")) {
                //if user is already logged we dont need to do anything 
                // so we redirect them to the main page
                return $this->response->redirect("user-home");
            }


            if ($this->request->isPost()) 
              {

                $email = $this->request->getPost("email");
                $password = $this->request->getPost("password");
                $password = sha1($password);

                if ($email === "") {
                    $this->flashSession->error("Warning :: Enter email");
                    return $this->view->pick("account/account-duplicate");
                }

                if ($password === "") {
                    $this->flashSession->error("Warning :: Enter password");
                    return $this->view->pick("account/account-duplicate");
                }
              }


            $user = Registration::findFirst("email = '$email' AND password='$password'");  
                
                if (false === $user) 
                {
                    $this->flashSession->error("Error :: Email / Password Mismatch");
                    return $this->view->pick("account/account-duplicate");
                } 
                else 
                {
                    date_default_timezone_set("Asia/Kuala_Lumpur");
                    $currenttime = date("Y-m-d H:i:s");
                    $res = $this->db->execute("UPDATE registration SET lastlogin = ? WHERE id = ?",array($currenttime,$user->id));
                    $sessions->set("user_id", $user->id);
                    $sessions->set("user_name", $user->name);
                    $sessions->set("user_photo", $user->photo);
                    $sessions->set("user_email", $user->email);

                    //$this->flashSession->success("Success :: Login Successful");
                    //return $this->view->pick("account/account-duplicate"); 
                    
                    //return $name;
                    return $this->response->redirect("user-home");                 
                }


    }


    // User home action after login
    public function userhomeAction()
    {
            
            $sessions = $this->getDI()->getShared("session");
            if ($sessions->has("user_id")) 
            {
                $email = $this->session->get('user_email');
                $id = $this->session->get('user_id');
                $data = Registration :: findFirst($id);
                $this->view->data = $data;

                //$dataComment = Comment :: find("email = '$email' ");
                


      $result = $this->modelsManager->createBuilder()
      ->columns("comment.comment, comment.postedat,item.name,item.photo,item.view,item.categoryid,item.id")
      ->From('comment')
      ->innerjoin('item', 'comment.productid = item.id')
      ->where("comment.email = '$email' ")
      ->getQuery()
      ->execute(); 

 

                $this->view->dataComment = $result;
                
                $this->view->pick('userhome/user-home');
            }
            else
            {
            return $this->response->redirect("create-account");
            } 
    }

    // Logout function
    public function logoutAction()
    {     
          $this->session->destroy(true);
          return $this->response->redirect("./");
    }

    // User profile pic change function
    public function changepicAction()
    {
        // Check if the user has uploaded files
        $userid = $this->request->getPost("userid");
        if ($this->request->hasFiles() == true) {
            $baseLocation = 'img/';

            // Print the real file names and sizes
            foreach ($this->request->getUploadedFiles() as $file) {
                             
                $filename = $file->getName();
                $res = $this->db->execute("UPDATE registration SET photo = ? WHERE id = ?",array($filename,$userid));
                //$photos->size = $file->getSize();
                //$photos->save();

                //Move the file into the application
                $file->moveTo($baseLocation . $file->getName());
                $sessions = $this->getDI()->getShared("session");
                $sessions->set("user_photo", $filename);
                $this->flashSession->success("success :: Profile pic changed.");
            }
        }

          $this->response->redirect('user-home');
        
    }

	// Profile save with information
    public function profilesaveAction()
    {
        $userid = $this->request->getPost("userid");
        $details = $this->request->getPost("detail");
        $social = $this->request->getPost("social");
        $location = $this->request->getPost("location");
        $profession = $this->request->getPost("profession");
        $res = $this->db->execute("UPDATE registration SET about = ?,location = ?,profession = ?,socialid = ? WHERE id = ?",array($details,$location,$profession,$social,$userid));
        $this->response->redirect('user-home');
    }


    /*public function pdfAction()
    {
        $pdf = new Pdf;
        $pdf->addPage('pdf/temp.phtml');
        if (!$pdf->saveAs('pdf/Pdf.pdf')) {
            throw new \Exception('Could not create PDF: '.$pdf->getError());
        }
    }*/

    public function csvAction()
    {
        $data_css = 'border:1px solid #ccc';
        $header_css = 'border:1px solid #ffc107';
        $email = $this->request->getPost("email");
        $sheet = $this->modelsManager->createBuilder()
              ->columns("comment.comment, comment.username, comment.email, comment.postedat,item.name,item.photo,item.view,item.categoryid,item.id")
              ->From('comment')
              ->innerjoin('item', 'comment.productid = item.id')
              ->where("comment.email = '$email' ")
              ->getQuery()
              ->execute(); 

        $table = "<table>
        <tr>
        <td style='$header_css'>Given Name</td>
        <td style='$header_css'>Email</td>
        <td style='$header_css'>Bird Name</td>
        <td style='$header_css'>Posted Time</td>
        <td style='$header_css'>Your comment</td>
        <td style='$header_css'>Total view by all </td>
        </tr>";
        foreach ($sheet as $row) {
            $table.= "<tr>
            <td style='$data_css'>$row->username</td>
            <td style='$data_css'>$row->email</td>
            <td style='$data_css'>$row->name</td>
            <td style='$data_css'>$row->postedat</td>
            <td style='$data_css'>$row->comment</td>
            <td style='$data_css'>$row->view</td>
            </tr>";
        }
        $table.= '</table>';
/*      header('Content-Encoding: UTF-8');
        header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header ("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header ("Cache-Control: no-cache, must-revalidate");
        header ("Pragma: no-cache");*/
        header ("Content-type: application/xls;charset=UTF-8");
        header ("Content-Disposition: attachment; filename=Activity.xls" );
        return $table;
        
        
            
        

    }


    public function pdfgenerateAction()
    {
        $data_css = 'border:1px solid #ccc';
        $header_css = 'border:1px solid #000000';
        $email = $this->request->getPost("email");
        
        $mpdf = new \Mpdf\Mpdf();

        $info = Registration::findFirst("email = '$email'");


        $mpdf->WriteHTML("<div align='center'><strong>Activity Report</strong> of <strong>$info->name</strong></div>");
        $intro ="<table width='100%'>
                        <tr>
                        <td >  <h3>Email : </h3>$info->email</td>
                        <td >  <h3>Phone : </h3>$info->phone</td>
                        <td  align='center'><h3>Profession : </h3>$info->profession</td>
                        <td ><img src='img/$info->photo' style='width:130px;'/></td>
                        </tr>
                        </table>";
        $mpdf->WriteHTML($intro);

        $mpdf->WriteHTML("<hr>");
        
         $sheet = $this->modelsManager->createBuilder()
              ->columns("comment.comment, comment.username, comment.email, comment.postedat,item.name,item.photo,item.view,item.categoryid,item.id")
              ->From('comment')
              ->innerjoin('item', 'comment.productid = item.id')
              ->where("comment.email = '$email' ")
              ->getQuery()
              ->execute();        

        $table = "<table>
        <tr>
        <td style='$header_css'>Given Name</td>
        <td style='$header_css'>Email</td>
        <td style='$header_css'>Bird Name</td>
        <td style='$header_css'>Posted Time</td>
        <td style='$header_css'>Your comment</td>
        <td style='$header_css'>Total view by all </td>
        </tr>";
        foreach ($sheet as $row) {
            $table.= "<tr>
            <td style='$data_css'>$row->username</td>
            <td style='$data_css'>$row->email</td>
            <td style='$data_css'>$row->name</td>
            <td style='$data_css'>$row->postedat</td>
            <td style='$data_css'>$row->comment</td>
            <td style='$data_css'>$row->view</td>
            </tr>";
        }
        $table.= '</table>';

        $mpdf->WriteHTML($table);
        $year = date('Y');
        $mpdf->WriteHTML("<hr>");
        $mpdf->WriteHTML("<div style='display:block;position:fixed;bottom:0px;height:30px;width:100%' align='center'><strong>Copyright &copy; Phalconify,$year</strong></div>");

        $filename = $info->id;
        $mpdf->Output("pdf/$filename.pdf", 'F');


        
        $mail = new Mail();
        $params = [
                     'name' => $info->name,
                     'link' => "http://localhost/CarrierPhalcon/pdf/$filename.pdf"                     
                 ];
         $mail->send_pdf($this->request->getPost('email', ['trim', 'email']), 'pdf', $params);

/*        $queue = new DbQueue();
        $queue->choose('email_notification'); //sets the tube we'll be using
        $queue->put($asad,[DbQueue::OPT_DELAY => 60 * 10]);*/

        //$this->view->pick('pdf/response');
        $this->flashSession->success("success :: PDF generated.An email has been sent.");
        $this->response->redirect('user-home');



    }



    public function subscriptionAction()
    {
       $email = $this->request->getPost("email");
       $user = Registration::findFirst("email = '$email'");
       $getid = $user->id;
       $checkAlreadySubscribed = Subscriptions::findFirst("user_id = $getid");
       if($checkAlreadySubscribed)
       {
        $this->flashSession->success("No need to subscribe again.Thanks for you support.");
               
       }
       else
       {

       $user->newSubscription('main', 'plan_DhTYRWOdmKGp1w')->create($this->getTestToken());
        $value = "Subscription $1 USD has been confirmed. ";
        $mail = new Mail();
        $params = [
                     'name' => $user->name,
                     'value' => $value                     
                 ];
         $mail->send_subscription($this->request->getPost('email', ['trim', 'email']), 'subscription', $params);
       $this->flashSession->success("success :: Thanks for supporting us.");
      
       }
       $this->response->redirect('user-home'); 

    }
    protected function getTestToken()
    {
        $card = '4242424242424242';
        $exp_month = 5;
        $year = 2020;
        $cvc = '123';
        return \Stripe\Token::create([
            'card' => [
                'number' => "$card",
                'exp_month' => $exp_month,
                'exp_year' => $year,
                'cvc' => "$cvc",
            ],
        ], ['api_key' => 'sk_test_hPN63FQvCqjmEcvvU2SjMGGa'])->id;


    $user->subscription('plan_DhTYRWOdmKGp1w')
    ->withCoupon('Ka0aobv1')
    ->create($stripe_token);
    }



////Content
    public function contentAction()
    {
            $sessions = $this->getDI()->getShared("session");
            if ($sessions->has("user_id")) 
            {
                $email = $this->session->get('user_email');
                $id = $this->session->get('user_id');
                $data = Registration :: findFirst($id);
                $this->view->data = $data;

                $allcategory = Category :: find();
                $this->view->allcategory = $allcategory;
                //$dataComment = Comment :: find("email = '$email' ");
                


      $result = $this->modelsManager->createBuilder()
      ->columns("comment.comment, comment.postedat,item.name,item.photo,item.view,item.categoryid,item.id")
      ->From('comment')
      ->innerjoin('item', 'comment.productid = item.id')
      ->where("comment.email = '$email' ")
      ->getQuery()
      ->execute(); 

 

                $this->view->dataComment = $result;
                
                $this->view->pick('userhome/content');
            }
            else
            {
            return $this->response->redirect("create-account");
            } 
    }





    public function addcategoryAction()
    {
      $userid     = $this->request->getPost('addedby');
      $categoryname     = $this->request->getPost('category');
      
      $check = Category::findFirst("categoryname = '$categoryname'");
      if($check == '')
      {

          $add = new Category();
          $add->addedby = $userid;
          $add->categoryname = $categoryname;
          if($add->save())
          {
            $this->flashSession->success("success :: New Category added.Category will be publish as soon as it gets any product.");
            return $this->response->redirect("content");
          }
          else
          {
            $this->flashSession->error("Error :: Login again and try");
            return $this->response->redirect("content");
          }
      }
      else
      {
          $this->flashSession->error("Error :: Already Available");
          return $this->response->redirect("content"); 
      }


    }


    public function addproductAction()
    {
      $userid     = $this->request->getPost('addedby');
      $categoryid     = $this->request->getPost('category');
      $productname     = $this->request->getPost('productname');
      $price     = $this->request->getPost('price');
      $synopsis     = $this->request->getPost('synopsis');

      $count= Item::count("categoryid = '$categoryid'");
      $updatedNumber = $count+1;

      if ($this->request->hasFiles() == true) 
      {
            $baseLocation = 'product/';

            // Print the real file names and sizes
            foreach ($this->request->getUploadedFiles() as $file)
             {
                             
                $filename = $file->getName();
                $file->moveTo($baseLocation . $file->getName());
                
                $add = new Item();
                $add->addedby = $userid;
                $add->categoryid = $categoryid;
                $add->name = $productname;
                $add->price = $price;
                $add->synopsis = $synopsis;
                $add->photo = $filename;

                if($add->save())
                {
                  
                  $res = $this->db->execute("UPDATE category SET totalitem = ? WHERE id = ?",array($updatedNumber,$categoryid));
                  $this->flashSession->success("success :: New Category added.");
                  return $this->response->redirect("content");

                }
                else
                {
                  $this->flashSession->error("Error :: Login again and try");
                  return $this->response->redirect("content");
                }


             }
        }


    }



    
}


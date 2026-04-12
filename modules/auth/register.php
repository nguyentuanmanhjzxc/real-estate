<?php
    
    include(__DIR__. "/../../config/database.php");
    session_start();
    $message = "";
    $type = "";

    if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    $input = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];


    if(empty($input) || empty($password) || empty($confirm)){
        $message = "Vui lòng nhập đầy đủ thông tin";
        $type = "error";
    }


    // if(strlen($password) < 8){
    //     $message = "Mật khẩu phải hơn 8 kí tự";
    //     $type= "error";
    // }
    
    if($password != $confirm){
        $message = "Mật khẩu không khớp";
        $type = "error";
    }else{

        // check là email hay số điện thoại
            if(filter_var($input,FILTER_VALIDATE_EMAIL)){
            $email = $input;
            $phone = NULL;
            $name = explode("@",$email)[0];

        }else if (preg_match('/^[0-9]{10,11}$/',$input)){
            $email = NULL;
            $phone = $input;
            $name = $phone;

        }else{
            $message= "Email hoặc số điện thoại không hợp lệ";
            $type= "error";

        }
        
        if(empty($type)){

            //check ton tai
            if(!empty($email)){
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? ");
                $stmt->bind_param("s",$email);
            }else{
                $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? ");
                $stmt->bind_param("s",$phone);
            }

            $stmt->execute();
            $result = $stmt->get_result();

             if($result->num_rows >0){
               $message= "Tài khoản đã tồn tại";
                $type = "error";
            }else{

              // INSERT
                $hash = password_hash($password,PASSWORD_DEFAULT);


                $stmt = $conn->prepare
                ("
                    INSERT INTO users (name, email, phone, password, role_id)
                    VALUES (?, ?, ?, ?, 2)
                ");

                $stmt->bind_param("ssss", $name, $email, $phone, $hash);

                if($stmt->execute()){
                    $message = "Đăng ký thành công";
                    $type = "success";
                }else{
                    $message = "Có lỗi xảy ra";
                    $type = "error";
                }
            }
            $stmt->close();
        }      
    }
       $_SESSION['register_status'] = $type;
       $_SESSION['register_msg'] = $message;

        header("Location:../../public/index.php");
        exit();
} 

?>

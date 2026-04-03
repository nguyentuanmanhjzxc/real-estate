<?php
    include(__DIR__. "/../config/database.php");
    session_start();    

    if($_SERVER["REQUEST_METHOD"] == "POST"){

        $input = $_POST['email'];
        $password = $_POST['password'];

        if(empty($input) || empty($password)){
            $_SESSION['login_status'] = 'error';
            $_SESSION['login_msg'] = 'Vui lòng nhập thông tin đầy đủ';
            header("Location: index.php");
            exit();
        }

        if(filter_var($input,FILTER_VALIDATE_EMAIL)){
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        }else{
            $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ?");
        }
    }
    
    $stmt->bind_param("s",$input);
    $stmt->execute();
    $result = $stmt->get_result();


    if($result->num_rows == 0){
       $_SESSION['login_status'] = 'error';
    $_SESSION['login_msg'] = 'Tài khoản không tồn tại';
        header("Location: index.php");
        exit();
    }

    $user = $result->fetch_assoc();

    if(!password_verify($password,$user['password'])){
          $_SESSION['login_status'] = 'error';
          $_SESSION['login_msg'] = 'Sai mật khẩu';
            header("Location: index.php");
            exit();
    }

    //luu session user
    $_SESSION['user'] = 
    [
        'id' => $user['id'],
        'name' => $user['name'],
        'role' => $user['role_id']
    ];

    header("Location: index.php");
    exit();


?>
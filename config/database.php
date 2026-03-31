<?php
 $conn = mysqli_connect("localhost","root","","real_estate");
 if(!$conn){
    die("Lỗi kết nối DataBase: ". mysqli_connect_errno());
 }
?>
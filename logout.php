<?php require 'config.php'; $_SESSION=[]; session_destroy(); session_start(); $_SESSION['flash']=['type'=>'success','message'=>'Berhasil logout.']; redirect('login.php');

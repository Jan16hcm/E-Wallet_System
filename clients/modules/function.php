<?php
    function connect_db(){
        $con = new mysqli("localhost", "root", "", "databasename");
        if ($con->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        return $con;
    }
    function verify_pic(){

    }
    function filter_account(){
        //acc wait for act
        //act acc
        //dis acc
        //locked acc
        //
    }
?>
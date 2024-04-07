<?php

    Class Database{
        private $server = "localhost";
        private $username = "root";
        private $password = "";
        private $dbname = "flyhigh";

        private $conn = null;
        private $state = false;
        private $errmsg = "";

        public function __construct(){
            try{
                $this->conn = new PDO("mysql:host=" . $this->server . ";dbname=" . $this->dbname, $this->username, $this->password);
                $this->conn->exec("set names utf8");
                $this->state = true;
                $this->errmsg = "Connected successfully";
           }catch(PDOException $exception){
               $this->errmsg =  "Connection error: " . $exception->getMessage();
               $this->state = false;
           }
        }

        public function getDb(){
            return $this->conn;
        }
        public function getState(){
            return $this->state;
        }
        public  function getErrMsg(){
            return $this->errmsg;
        }
    }
?>
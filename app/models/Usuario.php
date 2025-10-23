<?php
namespace App\Models;

class Usuario {
    private $USU_id;
    private $PER_id;
    private $USU_login;
    private $USU_pass;
    private $USU_permiso;
    private $USU_estado;
    private $cui;


    
    // Getters
    public function getUSU_ID() { return $this->USU_id; }
    public function getPER_id() { return $this->PER_id; }
    public function getUSU_login() { return $this->USU_login; }
    public function getUSU_pass() { return $this->USU_pass; }
    public function getUSU_permiso() { return $this->USU_permiso; }
    public function getUSU_estado() { return $this->USU_estado; }
    public function getCUI() { return $this->cui; }
    
    // Setters
    public function setUSU_ID($USU_id) { $this->USU_id = $USU_id; }
    public function setPER_id($PER_id) { $this->PER_id = $PER_id; }
    public function setUSU_login($USU_login) { $this->USU_login = $USU_login; }
    public function setUSU_pass($USU_pass) { $this->USU_pass = $USU_pass; }
    public function setUSU_permiso($USU_permiso) { $this->USU_permiso = $USU_permiso; }
    public function setUSU_estado($USU_estado) { $this->USU_estado = $USU_estado; }
    public function setCUI($cui) { $this->cui = $cui; }

    
    
    public function getNombreCompleto() {
        return "{$this->USU_login} {$this->USU_pass} {$this->USU_permiso}";
    }
    
    public function toArray() {
        return [
            'USU_id' => $this->USU_id,
            'PER_id' => $this->PER_id,
            'USU_login' => $this->USU_login,
            'USU_pass' => $this->USU_pass,
            'USU_permiso' => $this->USU_permiso,
            'cui' => $this->cui
        ];
    }
}
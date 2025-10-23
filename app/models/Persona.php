<?php
namespace App\Models;

class Persona {
    private $PER_id;
    private $PER_tipo;
    private $PER_documento_tipo;
    private $PER_documento_num;
    private $PER_nombre;
    private $PER_apellido_pat;
    private $PER_apellido_mat;
    private $PER_sexo;
    private $PER_estado_civil;
    private $PER_fecha_nac;
    private $PER_grado_instruccion;
    private $PER_carga_total;
    private $PER_carga_adultos;
    private $PER_carga_niños;
    private $PER_fono_fijo;
    private $PER_fono_movil;
    private $PER_email;
    private $UBI_id;
    private $SEC_id;
    private $PER_via_nombre;
    private $PER_via_numero;
    private $PER_via_mz;
    private $PER_via_lt;
    private $PER_foto;


    
    // Getters
    public function getPER_id() { return $this->PER_id; }
    public function getPER_tipo() { return $this->PER_tipo; }
    public function getPER_documento_tipo() { return $this->PER_documento_tipo; }
    public function getPER_documento_num() { return $this->PER_documento_num; }
    public function getPER_nombre() { return $this->PER_nombre; }
    public function getPER_apellido_pat() { return $this->PER_apellido_pat; }
    public function getPER_apellido_mat() { return $this->PER_apellido_mat; }
    public function getPER_sexo() { return $this->PER_sexo; }
    public function getPER_estado_civil() { return $this->PER_estado_civil; }
    public function getPER_fecha_nac() { return $this->PER_fecha_nac; }
    public function getPER_grado_instruccion() { return $this->PER_grado_instruccion; }
    public function getPER_carga_total() { return $this->PER_carga_total; }
    public function getPER_carga_adultos() { return $this->PER_carga_adultos; }
    public function getPER_carga_niños() { return $this->PER_carga_niños; }
    public function getPER_fono_fijo() { return $this->PER_fono_fijo; }
    public function getPER_fono_movil() { return $this->PER_fono_movil; }
    public function getPER_email() { return $this->PER_email; }
    public function getUBI_id() { return $this->UBI_id; }
    public function getSEC_id() { return $this->SEC_id; }
    public function getPER_via_nombre() { return $this->PER_via_nombre; }
    public function getPER_via_numero() { return $this->PER_via_numero; }
    public function getPER_via_mz() { return $this->PER_via_mz; }
    public function getPER_via_lt() { return $this->PER_via_lt; }
    public function getPER_foto() { return $this->PER_foto; }
    
    // Setters
    public function setPER_id($PER_id) { $this->PER_id = $PER_id; }
    public function setPER_tipo($PER_tipo) { $this->PER_tipo = $PER_tipo; }
    public function setPER_documento_tipo($PER_documento_tipo) { $this->PER_documento_tipo = $PER_documento_tipo; }
    public function setPER_documento_num($PER_documento_num) { $this->PER_documento_num = $PER_documento_num; }
    public function setPER_nombre($PER_nombre) { $this->PER_nombre = $PER_nombre; }
    public function setPER_apellido_pat($PER_apellido_pat) { $this->PER_apellido_pat = $PER_apellido_pat; }
    public function setPER_apellido_mat($PER_apellido_mat) { $this->PER_apellido_mat = $PER_apellido_mat; }
    public function setPER_sexo($PER_sexo) { $this->PER_sexo = $PER_sexo; }
    public function setPER_estado_civil($PER_estado_civil) { $this->PER_estado_civil = $PER_estado_civil; }
    public function setPER_fecha_nac($PER_fecha_nac) { $this->PER_fecha_nac = $PER_fecha_nac; }
    public function setPER_grado_instruccion($PER_grado_instruccion) { $this->PER_grado_instruccion = $PER_grado_instruccion; }
    public function setPER_carga_total($PER_carga_total) { $this->PER_carga_total = $PER_carga_total; }
    public function setPER_carga_adultos($PER_carga_adultos) { $this->PER_carga_adultos = $PER_carga_adultos; }
    public function setPER_carga_niños($PER_carga_niños) { $this->PER_carga_niños = $PER_carga_niños; }
    public function setPER_fono_fijo($PER_fono_fijo) { $this->PER_fono_fijo = $PER_fono_fijo; }
    public function setPER_fono_movil($PER_fono_movil) { $this->PER_fono_movil = $PER_fono_movil; }
    public function setPER_email($PER_email) { $this->PER_email = $PER_email; }
    public function setUBI_id($UBI_id) { $this->UBI_id = $UBI_id; }
    public function setSEC_id($SEC_id) { $this->SEC_id = $SEC_id; }
    public function setPER_via_nombre($PER_via_nombre) { $this->PER_via_nombre = $PER_via_nombre; }
    public function setPER_via_numero($PER_via_numero) { $this->PER_via_numero = $PER_via_numero; }
    public function setPER_via_mz($PER_via_mz) { $this->PER_via_mz = $PER_via_mz; }
    public function setPER_via_lt($PER_via_lt) { $this->PER_via_lt = $PER_via_lt; }
    public function setPER_foto($PER_foto) { $this->PER_foto = $PER_foto; }
    
    
    public function getNombreCompleto() {
        return "{$this->PER_nombre} {$this->PER_apellido_pat} {$this->PER_apellido_mat}";
    }
    
    public function toArray() {
        return [
            'PER_id' => $this->PER_id,
            'PER_tipo' => $this->PER_tipo,
            'PER_documento_tipo' => $this->PER_documento_tipo,
            'PER_documento_num' => $this->PER_documento_num,
            'PER_nombre' => $this->PER_nombre,
            'PER_apellido_pat' => $this->PER_apellido_pat,
            'PER_apellido_mat' => $this->PER_apellido_mat,
            'PER_sexo' => $this->PER_sexo,
            'PER_estado_civil' => $this->PER_estado_civil,
            'PER_fecha_nac' => $this->PER_fecha_nac,
            'PER_grado_instruccion' => $this->PER_grado_instruccion,
            'PER_carga_total' => $this->PER_carga_total,
            'PER_carga_adultos' => $this->PER_carga_adultos,
            'PER_carga_niños' => $this->PER_carga_niños,
            'PER_fono_fijo' => $this->PER_fono_fijo,
            'PER_email' => $this->PER_email,
            'UBI_id' => $this->UBI_id,
            'SEC_id' => $this->SEC_id,
            'PER_via_nombre' => $this->PER_via_nombre,
            'PER_via_numero' => $this->PER_via_numero,
            'PER_via_mz' => $this->PER_via_mz,
            'PER_via_lt' => $this->PER_via_lt,
            'PER_foto' => $this->PER_foto
        ];
    }
}
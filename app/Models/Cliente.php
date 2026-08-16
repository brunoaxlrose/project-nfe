<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Tenantable;
class Cliente extends Model { use Tenantable; protected $fillable=['empresa_id','razao_social','documento','inscricao_estadual','cep','logradouro','numero','complemento','bairro','cidade','codigo_ibge','uf','ativo']; protected $casts=['ativo'=>'boolean']; }

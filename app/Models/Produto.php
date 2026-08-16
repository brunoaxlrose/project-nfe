<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Tenantable;
class Produto extends Model { use Tenantable; protected $fillable=['empresa_id','codigo','descricao','ncm','valor_unitario','cfop','csosn','cst','unidade','ativo']; protected $casts=['ativo'=>'boolean','valor_unitario'=>'decimal:4']; }

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instance extends Model
{
    use HasFactory;

    protected $table = 'instances';
    protected $fillable = ['token', 'session_id', 'webhook', 'connected'];

    /**
     * Método para inicializar uma instância
     *
     * @param array $data
     * 
     * @return Instance
     */
    public static function initInstance(array $data): Instance
    {
        // Verifica se a instância já existe
        $query = self::where(['session_id' => $data['session_id']]);
        if($query->exists()) {
            $query->update($data);
            return $query->first();
        }

        // Caso não exista, cria uma nova instância
        return self::create($data);
    }
}

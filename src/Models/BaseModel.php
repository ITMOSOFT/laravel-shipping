<?php
namespace ItmoSoft\Shipping\Models;
use Illuminate\Database\Eloquent\Model;
abstract class BaseModel extends Model
{
    public function getTable(): string
    {
        $table = parent::getTable();
        $prefix = (string) config('itmosoft-shipping.table_prefix', 'shipping_');
        return str_starts_with($table, $prefix) ? $table : $prefix.$table;
    }
}

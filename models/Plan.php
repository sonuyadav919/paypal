<?php namespace Creations\PayPal\Models;

use Model;

/**
 * Plan Model
 */
class Plan extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'creations_paypal_plans';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

}

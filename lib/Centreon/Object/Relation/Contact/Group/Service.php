<?php

require_once "Centreon/Object/Relation/Relation.php";

class Centreon_Object_Relation_Contact_Group_Service extends Centreon_Object_Relation
{
    protected $relationTable = "contactgroup_service_relation";
    protected $firstKey = "contactgroup_cg_id";
    protected $secondKey = "service_service_id";

	/**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->firstObject = new Centreon_Object_Contact_Group();
        $this->secondObject = new Centreon_Object_Service();
    }
}
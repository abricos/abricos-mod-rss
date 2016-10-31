<?php
/**
 * @package Abricos
 * @subpackage RSS
 * @copyright 2008-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';

class RSSManager extends Ab_ModuleManager {

    /**
     *
     * @var RSSModule
     */
    public $module = null;

    public function __construct(RSSModule $module){
        parent::__construct($module);
    }

    public function IsAdminRole(){
        return $this->IsRoleEnable(RSSAction::ADMIN);
    }

    public function IsManagerRole(){
        return $this->IsRoleEnable(RSSAction::MANAGER);
    }

    public function IsViewRole(){
        return $this->IsRoleEnable(RSSAction::VIEW);
    }
}

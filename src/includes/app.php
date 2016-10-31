<?php
/**
 * @package Abricos
 * @subpackage RSS
 * @copyright 2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class RSSApp
 *
 * @property RSSManager $manager
 */
class RSSApp extends AbricosApplication {

    protected function GetClasses(){
        return array();
    }

    protected function GetStructures(){
        return '';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
        }
        return null;
    }

    public function IsAdminRole(){
        return $this->manager->IsAdminRole();
    }

    public function IsManagerRole(){
        return $this->manager->IsManagerRole();
    }

    public function IsViewRole(){
        return $this->manager->IsViewRole();
    }


    public function ChanelAppend($d){
        if (!$this->IsAdminRole()){
            return;
        }
        return RSSQuery::ChanelAppend($this->db, $d);
    }

    public function ChanelUpdate($d){
        if (!$this->IsAdminRole()){
            return;
        }
        RSSQuery::ChanelUpdate($this->db, $d);
    }

    public function ChanelRemove($chanelid){
        if (!$this->IsAdminRole()){
            return;
        }
        RSSQuery::ChanelRemove($this->db, $chanelid);
    }

    public function SourceAppend($d){
        if (!$this->IsAdminRole()){
            return;
        }
        return RSSQuery::SourceAppend($this->db, $d);
    }

    public function SourceUpdate($d){
        if (!$this->IsAdminRole()){
            return;
        }
        RSSQuery::SourceUpdate($this->db, $d);
    }

    public function SourceRemove($sourceid){
        if (!$this->IsAdminRole()){
            return;
        }
        RSSQuery::SourceRemove($this->db, $sourceid);
    }

    public function ModuleList(){
        if (!$this->IsAdminRole()){
            return;
        }
        $mods = Abricos::$modules->RegisterAllModule();
        $arr = array();
        foreach ($mods as $childmod){
            if (!method_exists($childmod, 'RssMetaLink')){
                continue;
            }
            $row = array();
            $row['nm'] = $childmod->name;
            array_push($arr, $row);
        }
        return $arr;
    }

    public function Config($mod){
        if (!$this->IsAdminRole()){
            return;
        }
        return $this->module->GetPhrases()->ToAJAX();
    }

    public function ConfigUpdate($name, $value){
        if (!$this->IsAdminRole()){
            return;
        }
        $this->module->GetPhrases()->Set($name, $value);
        Abricos::$phrases->Save();
    }

    public function ChanelList(){
        if (!$this->IsViewRole()){
            return;
        }
        return RSSQuery::ChanelList($this->db);
    }

    public function SourceList(){
        if (!$this->IsAdminRole()){
            return;
        }
        return RSSQuery::SourceList($this->db);
    }

    public function ChanelSourceList(){
        if (!$this->IsAdminRole()){
            return;
        }
        return RSSQuery::ChanelSourceList($this->db);
    }

    private function Grabber($chanel){
        require_once 'grabber.php';
        $grabber = new RSSGrabber($chanel);
    }

    public function RecordList($chanelid){
        if (!$this->IsViewRole()){
            return;
        }
        $chanel = RSSQuery::Chanel($this->db, $chanelid);
        if (empty($chanel)){
            $chanel = RSSQuery::ChanelFirst($this->db);
        }
        if (empty($chanel)){
            return;
        }

        $this->Grabber($chanel);

        return RSSQuery::RecordList($this->db, $chanelid, $chanel['gcnt']);
    }

    public function Online(){
        if (!$this->IsViewRole()){
            return;
        }
        $chanels = RSSQuery::ChanelList($this->db);
        while (($chanel = $this->db->fetch_array($chanels))){
            $this->Grabber($chanel);
        }
        return RSSQuery::RecordList($this->db, 0, 10);
    }
}

<?php
namespace CT;

class CT_Link implements \JsonSerializable {

    private $link_id;
    private $link_sha256;
    private $link_key;
    private $deleted;
    private $context_id;
    private $path;
    private $lti13_lineitem;
    private $title;
    private $json;
    private $settings;
    private $settings_url;
    private $placementsecret;
    private $oldplacementsecret;
    private $entity_version;


    public function __construct($link_id = null) {
        $context = array();
        if (isset($link_id)) {
            $query = \CT\CT_DAO::getQuery('link', 'getByLinkId');
            $arr = array(':link_id' => $link_id);
            $context = $query['PDOX']->rowDie($query['sentence'], $arr);
        }
        \CT\CT_DAO::setObjectPropertiesFromArray($this, $context);
    }

    //get the link from de db by link_key
    public static function withLinkKey($link_key = null) {
        $link = null;
        if(isset($link_key)) {
            $query = \CT\CT_DAO::getQuery('link', 'getByLinkKey');
            $arr = array(
                ':link_key' => $link_key,
            );
            $link = new CT_Link();
            $links = $query['PDOX']->rowDie($query['sentence'], $arr);
            \CT\CT_DAO::setObjectPropertiesFromArray($link, $links);
        }
        return $link;
    }

    //necessary to use json_encode with exercise objects
    public function jsonSerialize() : mixed {
        return [
            'link_id' => $this->getLinkId(),
            'link_sha256' => $this-getLinkSha256(),
            'link_key' => $this->getLinkKey(),
            'title' => $this->getTitle(),
            'deleted' => $this->getDeleted(),
            'context_id' => $this->getContextId(),
            'path' => $this->getPath(),
            'lti13_lineitem' => $this->getLti13Lineitem(),
            'json' => $this->getJson(),
            'settings' => $this->getSettings(),
            'settings_url' => $this->getSettingsUrl(),
            'placementsecret' => $this->getPlacementsecret(),
            'oldplacementsecret' => $this->getOldplacementsecret(),
            'entity_version' => $this->getEntityVersion()
        ];
    }

    /**
     * @param CT_Link linkOriginal
     */
    public function import($ctMainOriginal, $ctMainCopy) {
        $zipFinalFilename = createZipFileForExportContext($ctMainOriginal);
        importContextFromZipFile($ctMainCopy, $zipFinalFilename);
        unlink($zipFinalFilename);
    }

    /**
     * Get the ct_main
     */
    public function getCtMain()
    {
        return \CT\CT_Main::getMainFromLinkId($this->getLinkId());
    }


    /**
     * Get the value of link_id
     */
    public function getLinkId()
    {
        return $this->link_id;
    }

    /**
     * Set the value of link_id
     */
    public function setLinkId($link_id): self
    {
        $this->link_id = $link_id;

        return $this;
    }


    /**
     * Get the value of link_sha256
     */
    public function getLinkSha256()
    {
        return $this->link_sha256;
    }

    /**
     * Set the value of link_sha256
     */
    public function setLinkSha256($link_sha256): self
    {
        $this->link_sha256 = $link_sha256;

        return $this;
    }

    /**
     * Get the value of link_key
     */
    public function getLinkKey()
    {
        return $this->link_key;
    }

    /**
     * Set the value of link_key
     */
    public function setLinkKey($link_key): self
    {
        $this->link_key = $link_key;

        return $this;
    }

    /**
     * Get the value of deleted
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set the value of deleted
     */
    public function setDeleted($deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get the value of context_id
     */
    public function getContextId()
    {
        return $this->context_id;
    }

    /**
     * Set the value of context_id
     */
    public function setContextId($context_id): self
    {
        $this->context_id = $context_id;

        return $this;
    }

    /**
     * Get the value of path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the value of path
     */
    public function setPath($path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the value of lti13_lineitem
     */
    public function getLti13Lineitem()
    {
        return $this->lti13_lineitem;
    }

    /**
     * Set the value of lti13_lineitem
     */
    public function setLti13Lineitem($lti13_lineitem): self
    {
        $this->lti13_lineitem = $lti13_lineitem;

        return $this;
    }

    /**
     * Get the value of title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of title
     */
    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of json
     */
    public function getJson()
    {
        return $this->json;
    }

    /**
     * Set the value of json
     */
    public function setJson($json): self
    {
        $this->json = $json;

        return $this;
    }

    /**
     * Get the value of settings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set the value of settings
     */
    public function setSettings($settings): self
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Get the value of settings_url
     */
    public function getSettingsUrl()
    {
        return $this->settings_url;
    }

    /**
     * Set the value of settings_url
     */
    public function setSettingsUrl($settings_url): self
    {
        $this->settings_url = $settings_url;

        return $this;
    }

    /**
     * Get the value of placementsecret
     */
    public function getPlacementsecret()
    {
        return $this->placementsecret;
    }

    /**
     * Set the value of placementsecret
     */
    public function setPlacementsecret($placementsecret): self
    {
        $this->placementsecret = $placementsecret;

        return $this;
    }

    /**
     * Get the value of oldplacementsecret
     */
    public function getOldplacementsecret()
    {
        return $this->oldplacementsecret;
    }

    /**
     * Set the value of oldplacementsecret
     */
    public function setOldplacementsecret($oldplacementsecret): self
    {
        $this->oldplacementsecret = $oldplacementsecret;

        return $this;
    }

    /**
     * Get the value of entity_version
     */
    public function getEntityVersion()
    {
        return $this->entity_version;
    }

    /**
     * Set the value of entity_version
     */
    public function setEntityVersion($entity_version): self
    {
        $this->entity_version = $entity_version;

        return $this;
    }
}

<?php
namespace Rehike\Model\Rehike\Version;

use Rehike\i18n\i18n;

class MVersionPage
{
    public $headingText;
    public $brandName;
    public $version = ""; // This gets replaced later
    public $nightlyNotice;
    public $nightlyInfo;
    public $failedNotice;
    public $nonGitNotice;
    public $extraInfo;

    protected $isNightly = false;

    public function __construct($data)
    {
        $strings = i18n::getNamespace('rehike/version');

        $this->headingText = $strings->get("headingVersionInfo");
        $this->brandName = $strings->get("brandName");

        if (@$data["semanticVersion"])
        {
            $this->version = $strings->format("versionHeader", $data["semanticVersion"]);
        }

        if (!@$data["isRelease"] && null != $data)
        {
            $this->nightlyNotice = new MNightlyNotice();
            $this->nightlyInfo = new MNightlyInfo($data);
            $this->isNightly = true;
        }

        if (null == $data)
        {
            $this->failedNotice = new MFailedNotice();
			unset($this->brandName);
			return;
        }

        if (!@$data["supportsDotGit"])
        {
            $this->nonGitNotice = new MNonGitNotice();
        }

        $this->extraInfo = new MExtraInfo();
    }
}
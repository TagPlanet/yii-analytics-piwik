<?php
/**
 * Piwik Component
 *
 * @author Philip Lawrence <philip@misterphilip.com>
 * @link http://misterphilip.com
 * @link http://tagpla.net
 * @link https://github.com/TagPlanet/yii-analytics-piwik
 * @copyright Copyright &copy; 2012 Philip Lawrence
 * @license http://tagpla.net/licenses/MIT.txt
 * @version 1.0.2
 */
class TPPiwikAnalytics extends CApplicationComponent
{
    /**
     * Site ID
     * @var string
     */
    public $siteID;
    
    /**
     * Tracker URL
     * @var string
     */
    public $trackerURL;

    /**
     * Auto render or return the JS
     * @var bool
     */
    public $autoRender = false;

    /**
     * Automatically add trackPageView when render is called
     * @var bool
     */
    public $autoPageview = true;
    
    /**
     * JS Variable name
     * @var string
     */
    public $variableName = '_paq';

    /**
     * Type of quotes to use for values
     */
    const Q = "'";

    /**
     * Available options, pulled (Oct 17, 2012) from
     * http://piwik.org/docs/javascript-tracking/#toc-list-of-all-methods-available-in-the-tracking-api
     * @var array
     */
    protected $_availableOptions = array
    (
        'addDownloadExtensions',
        'addEcommerceItem',
        'deleteCustomVariable',
        'disableCookies',
        'discardHashTag',
        'enableLinkTracking',
        'killFrame',
        'redirectFile',
        'setCampaignKeywordKey',
        'setCampaignNameKey',
        'setConversionAttributionFirstReferrer',
        'setCookieDomain',
        'setCookieNamePrefix',
        'setCookiePath',
        'setCountPreRendered',
        'setCustomUrl',
        'setCustomVariable',
        'setDoNotTrack',
        'setDocumentTitle',
        'setDomains',
        'setDownloadClasses',
        'setDownloadExtensions',
        'setHeartBeatTimer',
        'setIgnoreClasses',
        'setLinkClasses',
        'setLinkTrackingTimer',
        'setReferralCookieTimeout',
        'setReferrerUrl',
        'setRequestMethod',
        'setSessionCookieTimeout',
        'setSiteId',
        'setTrackerUrl',
        'setVisitorCookieTimeout',
        'trackEcommerceOrder',
        'trackEcommerceCartUpdate',
        'trackGoal',
        'trackPageView',
        'trackSiteSearch',
    );

    /**
     * An array of all the methods called for _gaq
     * @var array
     */
    protected $_calledOptions = array();

    /**
     * Method data to be pushed into the _gaq object
     * @var array
     */

    private $_data = array();

    /**
     * init function - Yii automaticall calls this
     */
    public function init()
    {
        // Verify we have the basics
        if($this->siteID == '') 
            throw new CException('Missing required parameter "Site ID" for TPPiwikAnalytics');
        if($this->trackerURL == '') 
            throw new CException('Missing required parameter "Tracker URL" for TPPiwikAnalytics');
        
        $this->setSiteId($this->siteID);
        $this->trackerURL = rtrim($this->trackerURL, '/');
        $this->setTrackerUrl($this->trackerURL . '/piwik.php');
    }

    /**
     * Render and return the Piwik code
     * @return mixed
     */
    public function render()
    {
        // Check to see if we need to throw in the trackPageview call
        if(!in_array('trackPageView', $this->_calledOptions) && $this->autoPageview)
        {
            $this->trackPageView();
        }
        
        // Start the JS string
        $js = 'var ' . $this->variableName . ' = ' . $this->variableName . ' || [];' . PHP_EOL;
        $js.= '(function() { ' . PHP_EOL;
        
        foreach($this->_data as $data)
        {                
            // Clean up each item
            foreach($data as $key => $item)
            {
                
                if(is_string($item))
                {
                    $data[$key] = self::Q . preg_replace('~(?<!\\\)'. self::Q . '~', '\\' . self::Q, $item) . self::Q;
                }
                else if(is_bool($item))
                {
                    $data[$key] = ($item) ? 'true' : 'false';
                }
                
                $prefixed = true;
            }

            $js.= '  ' . $this->variableName . '.push([' . implode(',', $data) . ']);' . PHP_EOL;
        }
        $js.= <<<EOJS
  // Call the file
  var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
  g.type='text/javascript'; g.defer=true; g.async=true; g.src='{$this->trackerURL}/piwik.js';
  s.parentNode.insertBefore(g,s); 
})();
// Piwik Extension provided by TagPla.net
// https://github.com/TagPlanet/yii-analytics-piwik
// Copyright 2013, TagPla.net & Philip Lawrence
EOJS;
        // Should we auto add in the analytics tag?
        if($this->autoRender)
        {
            Yii::app()->clientScript
                    ->registerScript('TPPiwikAnalytics', $js, CClientScript::POS_HEAD);
            return;
        }
        else
        {
            return $js;
        }
    }

    /**
     * Magic Method for options
     * @param string $name
     * @param array  $arguments
     */
    public function __call($name, $arguments)
    {
        if(in_array($name, $this->_availableOptions))
        {
            $this->_push($name, $arguments);
            return true;
        }
        return false;
    }

    /**
     * Push data into the array
     * @param string $variable
     * @param array  $arguments
     * @protected
     */
    protected function _push($variable, $arguments)
    {
        $data = array_merge(array($variable), $arguments);
        array_push($this->_data, $data);
        $this->_calledOptions[] = $variable;
    }
}
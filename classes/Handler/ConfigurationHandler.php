<?php

namespace PrestaShop\Module\PrestashopFacebook\Handler;

use PrestaShop\Module\PrestashopFacebook\Adapter\ConfigurationAdapter;
use PrestaShop\Module\PrestashopFacebook\API\FacebookClient;
use PrestaShop\Module\PrestashopFacebook\Config\Config;
use PrestaShop\Module\PrestashopFacebook\Provider\FacebookDataProvider;

class ConfigurationHandler
{
    /**
     * @var ConfigurationAdapter
     */
    private $configurationAdapter;

    /**
     * @var FacebookDataProvider
     */
    private $facebookDataProvider;

    /**
     * @var FacebookClient
     */
    private $facebookClient;

    public function __construct(
        ConfigurationAdapter $configurationAdapter,
        FacebookDataProvider $facebookDataProvider,
        FacebookClient $facebookClient
    ) {
        $this->configurationAdapter = $configurationAdapter;
        $this->facebookDataProvider = $facebookDataProvider;
        $this->facebookClient = $facebookClient;
    }

    public function handle($onboardingInputs)
    {
        $this->addFbeAttributeIfMissing($onboardingInputs);
        $this->saveOnboardingConfiguration($onboardingInputs);

        $facebookContext = $this->facebookDataProvider->getContext($onboardingInputs['fbe']);

        return [
            'success' => true,
            'contextPsFacebook' => $facebookContext,
        ];
    }

    /**
     * Call the FB API to uninstall FBE on their side, then clean the database
     */
    public function uninstallFbe()
    {
        $this->facebookClient->uninstallFbe(
            $this->configurationAdapter->get(Config::PS_FACEBOOK_EXTERNAL_BUSINESS_ID),
            $this->configurationAdapter->get(Config::PS_FACEBOOK_USER_ACCESS_TOKEN)
        );

        // Whatever the API answer, we drop the data on the configuration table
        // For instance, a user who already uninstalled FBE will get an error while calling the API.
        $this->cleanOnboardingConfiguration();
    }

    private function addFbeAttributeIfMissing(array &$onboardingParams)
    {
        if (!empty($onboardingParams['fbe']) && !isset($onboardingParams['fbe']['error'])) {
            return;
        }

        $this->facebookClient->setAccessToken($onboardingParams['access_token']);
        $onboardingParams['fbe'] = $this->facebookClient->getFbeAttribute($this->configurationAdapter->get(Config::PS_FACEBOOK_EXTERNAL_BUSINESS_ID));
    }

    private function saveOnboardingConfiguration(array $onboardingParams)
    {
        $this->configurationAdapter->updateValue(Config::PS_FACEBOOK_USER_ACCESS_TOKEN, $onboardingParams['access_token']);
        $this->configurationAdapter->updateValue(Config::PS_PIXEL_ID, isset($onboardingParams['fbe']['pixel_id']) ? $onboardingParams['fbe']['pixel_id'] : '');
        $this->configurationAdapter->updateValue(Config::PS_FACEBOOK_PROFILES, isset($onboardingParams['fbe']['profiles']) ? implode(',', $onboardingParams['fbe']['profiles']) : '');
        $this->configurationAdapter->updateValue(Config::PS_FACEBOOK_PAGES, isset($onboardingParams['fbe']['pages']) ? implode(',', $onboardingParams['fbe']['pages']) : '');
        $this->configurationAdapter->updateValue(Config::PS_FACEBOOK_BUSINESS_MANAGER_ID, isset($onboardingParams['fbe']['business_manager_id']) ? $onboardingParams['fbe']['business_manager_id'] : '');
        $this->configurationAdapter->updateValue(Config::PS_FACEBOOK_AD_ACCOUNT_ID, isset($onboardingParams['fbe']['ad_account_id']) ? $onboardingParams['fbe']['ad_account_id'] : '');
        $this->configurationAdapter->updateValue(Config::PS_FACEBOOK_CATALOG_ID, isset($onboardingParams['fbe']['catalog_id']) ? $onboardingParams['fbe']['catalog_id'] : '');
        $this->configurationAdapter->updateValue(Config::PS_FACEBOOK_PIXEL_ENABLED, true);
    }

    private function cleanOnboardingConfiguration()
    {
        $dataConfigurationKeys = [
            Config::PS_FACEBOOK_USER_ACCESS_TOKEN,
            Config::PS_PIXEL_ID,
            Config::PS_FACEBOOK_PROFILES,
            Config::PS_FACEBOOK_PAGES,
            Config::PS_FACEBOOK_BUSINESS_MANAGER_ID,
            Config::PS_FACEBOOK_AD_ACCOUNT_ID,
            Config::PS_FACEBOOK_CATALOG_ID,
            Config::PS_FACEBOOK_PIXEL_ENABLED,
        ];

        foreach ($dataConfigurationKeys as $key) {
            $this->configurationAdapter->deleteByName($key);
        }
    }
}

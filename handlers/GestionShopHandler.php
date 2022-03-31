<?php

/*
 * This file is part of the YesWiki Extension Shop.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Shop;

use Configuration;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Shop\Service\HelloAssoService;
use YesWiki\Shop\Service\MangoPayService;
use YesWiki\Shop\Service\ShopService;
use YesWiki\Security\Controller\SecurityController;

class GestionShopHandler extends YesWikiHandler
{
    // data
    private $serviceName;
    private $mangoPayParams;
    private $helloAssoParams;

    // services
    protected $shopService;

    public function run()
    {
        if ($this->getService(SecurityController::class)->isWikiHibernated()) {
            throw new \Exception(_t('WIKI_IN_HIBERNATION'));
        };
        if (!$this->wiki->UserIsAdmin()) {
            return $this->render('@templates/alert-message.twig', [
                'type'=>'danger',
                'message'=> "GestionShopHandler : " . _t('BAZ_NEED_ADMIN_RIGHTS')
            ]) ;
        }

        // get services
        $this->shopService = $this->getService(ShopService::class);

        // init contact Details
        $this->mangoPayParams = [];
        foreach (MangoPayService::MANGOPAY_PARAMS_NAMES as $paramName => $translation) {
            $this->mangoPayParams[$paramName] = $this->params->get('shop')['mangoPayParams'][$paramName] ?? '';
        }
        $this->helloAssoParams = [];
        foreach (HelloAssoService::HELLOASSO_PARAMS_NAMES as $paramName => $translation) {
            $this->helloAssoParams[$paramName] = $this->params->get('shop')['helloAssoParams'][$paramName] ?? '';
        }
        $this->serviceName = $this->shopService->getServiceName();

        if (isset($_POST['valider'])) {
            $messages = $this->update();
        }

        return $this->renderInSquelette('@shop/gestion-shop.twig', [
            'messages' => $messages ?? null,
            'mangoPayParams' => $this->mangoPayParams,
            'mangoPayParamsNames' => MangoPayService::MANGOPAY_PARAMS_NAMES,
            'helloAssoParams' => $this->helloAssoParams,
            'helloAssoParamsNames' => HelloAssoService::HELLOASSO_PARAMS_NAMES,
            // BE CAREFULL AT THIS STATE $this->serviceName could be different from $this->params->get('shop')['serviceName']
            // because wiki is not reloaded after $this->update()
            'serviceName' => $this->serviceName,
        ]);
    }

    private function update()
    {
        $output = '';
        if (!empty($_POST['mangoPayParams']) && is_array($_POST['mangoPayParams'])) {
            $output .= $this->updateMangoPayParams($_POST['mangoPayParams']);
        }
        if (!empty($_POST['helloAssoParams']) && is_array($_POST['helloAssoParams'])) {
            $output .= $this->updateHelloAssoParams($_POST['helloAssoParams']);
        }
        if (isset($_POST['serviceName']) && is_string($_POST['serviceName'])) {
            $output .= $this->updateServiceName($_POST['serviceName']);
        }
        return $output;
    }

    private function updateMangoPayParams(array $newParams)
    {
        $configNeedsToBeUpdated = false;
        foreach (MangoPayService::MANGOPAY_PARAMS_NAMES as $paramName => $translation) {
            $newValue = trim($newParams[$paramName]);
            if ((!empty($newValue) || !empty($this->mangoPayParams[$paramName])) &&
                $newValue !== $this->mangoPayParams[$paramName]) {
                $configNeedsToBeUpdated = true;
                $this->mangoPayParams[$paramName] = $newValue;
            }
        }

        if ($configNeedsToBeUpdated) {
            // save config
            
            include_once 'tools/templates/libs/Configuration.php';
            $config = new Configuration('wakka.config.php');
            $config->load();

            $baseKey = 'shop';
            $tmp = isset($config->$baseKey) ? $config->$baseKey : [];
            $tmp['mangoPayParams'] = $this->mangoPayParams;

            $config->$baseKey = $tmp;
            $config->write();
            unset($config);
            
            return $this->render('@templates/alert-message.twig', [
                'type' => 'success',
                'message' => _t('SHOP_UPDATE_MANGOPAY_PARAMS'),
            ]);
        }
    }
    private function updateHelloAssoParams(array $newParams)
    {
        $configNeedsToBeUpdated = false;
        foreach (HelloAssoService::HELLOASSO_PARAMS_NAMES as $paramName => $translation) {
            $newValue = trim($newParams[$paramName]);
            if ((!empty($newValue) || !empty($this->helloAssoParams[$paramName])) &&
                $newValue !== $this->helloAssoParams[$paramName]) {
                $configNeedsToBeUpdated = true;
                $this->helloAssoParams[$paramName] = $newValue;
            }
        }

        if ($configNeedsToBeUpdated) {
            // save config
            
            include_once 'tools/templates/libs/Configuration.php';
            $config = new Configuration('wakka.config.php');
            $config->load();

            $baseKey = 'shop';
            $tmp = isset($config->$baseKey) ? $config->$baseKey : [];
            $tmp['helloAssoParams'] = $this->helloAssoParams;

            $config->$baseKey = $tmp;
            $config->write();
            unset($config);
            
            return $this->render('@templates/alert-message.twig', [
                'type' => 'success',
                'message' => _t('SHOP_UPDATE_HELLOASSO_PARAMS'),
            ]);
        }
    }

    private function updateServiceName(string $serviceName)
    {
        $configNeedsToBeUpdated = false;
        $newValue = $this->shopService->getServiceName(trim($serviceName));
        if ((!empty($newValue) || !empty($this->serviceName)) &&
            $newValue !== $this->serviceName) {
            $configNeedsToBeUpdated = true;
            $this->serviceName = $newValue;
        }

        if ($configNeedsToBeUpdated) {
            // save config
            
            include_once 'tools/templates/libs/Configuration.php';
            $config = new Configuration('wakka.config.php');
            $config->load();

            $baseKey = 'shop';
            $tmp = isset($config->$baseKey) ? $config->$baseKey : [];
            $tmp['serviceName'] = $this->serviceName;

            $config->$baseKey = $tmp;
            $config->write();
            unset($config);
            
            return $this->render('@templates/alert-message.twig', [
                'type' => 'success',
                'message' => _t('SHOP_UPDATE_SERVICENAME'),
            ]);
        }
    }
}

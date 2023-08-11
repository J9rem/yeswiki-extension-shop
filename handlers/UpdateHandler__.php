<?php

namespace YesWiki\Shop;

use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\ListManager;
use YesWiki\Core\Service\ConfigurationService;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Security\Controller\SecurityController;

class UpdateHandler__ extends YesWikiHandler
{
    private const PATHS = [
        'forms' => [
            'Produit' => 'tools/shop/setup/forms/Form - Produit.txt',
        ],
        'lists' => [
            'OuiNonShop' => 'tools/shop/setup/lists/OuiNonShop.json'
        ]
    ];

    public function run(): ?string
    {
        if ($this->getService(SecurityController::class)->isWikiHibernated()) {
            throw new \Exception(_t('WIKI_IN_HIBERNATION'));
        };
        if (!$this->wiki->UserIsAdmin()) {
            return null;
        }

        $message = $this->installDefaultList();

        $formIdsParam = $this->params->get('shop')['forms']['products'] ?? '';
        $productsFormIds = array_filter(array_map('trim', explode(',', $formIdsParam)));

        if (empty($productsFormIds)) {
            $formId = $this->createDefaultForm();
            $this->updateWakkaConfig($formId);
            $message .= empty($formId)
                ? "<br/>❌ error when creating a new products' form"
                : "<br/>✅ New form $formId created with new content";
        }

        foreach ($productsFormIds as $productsFormId) {
            if (strval($productsFormId) == strval(intval($productsFormId)) && intval($productsFormId) > 0) {
                $returnTxt = $this->getFormOrCreate($productsFormId);
                $message .= empty($returnTxt)
                    ? "<br/>ℹ️ form $productsFormId already existing "
                    : "<br/>✅ form $productsFormId created";
            } else {
                $message .= "<br/>❌ formId <strong>'$productsFormId'</strong> should be a positive integer";
            }
        }

        // set output
        $message = <<<HTML
        <b>Extension shop</b>
        $message<br/>
        <hr/>
        HTML;
        $this->output = str_replace(
            '<!-- end handler /update -->',
            $message.'<!-- end handler /update -->',
            $this->output
        );
        return null;
    }

    /**
     * @param string $formId
     * @return string $formId if created
     */
    private function getFormOrCreate(string $formId): string
    {
        // get services
        $formManager = $this->getService(FormManager::class);
        if (!empty($formId)) {
            // get Form
            $form = $formManager->getOne($formId);
            return empty($form)
                ? $this->createDefaultForm($formId)
                : '';
        }
        return '';
    }

    private function createDefaultForm(string $formId = ''): string
    {
        $formManager = $this->getService(FormManager::class);

        if (!file_exists(self::PATHS['forms']['Produit'])) {
            $this->render('@templates/alert-message.twig', [
                'type' => 'danger',
                'message' => str_replace(
                    ['{formName}', '{filePath}'],
                    ['Produit', self::PATHS['forms']['Produit']],
                    _t('SHOP_UPDATE_FORM_ERROR')
                ),
            ]);
            return '';
        }

        $formTemplate = file_get_contents(self::PATHS['forms']['Produit']);

        if (empty($formId)){
            $formId = $formManager->findNewId();
        }
        if (!$formManager->create([
            'bn_label_nature' => 'Produit',
            'bn_template' => $formTemplate,
            'bn_description' => '',
            'bn_sem_context' => '',
            'bn_sem_type' => '',
            'bn_condition' => '',
            'bn_id_nature' => $formId,
        ])) {
            return '';
        };

        return strval($formId);
    }

    private function updateWakkaConfig($formId): void
    {
        if (!empty($formId)) {
            $configurationService = $this->getService(ConfigurationService::class);
            $config = $configurationService->getConfiguration('wakka.config.php');
            $config->load();

            $baseKey = 'shop';
            $tmp = $config->$baseKey ?? [];
            if (!isset($tmp['forms']['products'])) {
                $tmp['forms'] = [
                    'products' => strval($formId)
                ];
            } else {
                $tmp['forms']['products'] = $tmp['forms']['products'] . ",$formId";
            }


            $config->$baseKey = $tmp;
            $config->write();

            unset($config);
        }
    }

    private function installDefaultList(): string
    {
        $listManager = $this->getService(ListManager::class);
        $listeValues = json_decode(file_get_contents(self::PATHS['lists']['OuiNonShop']), true);

        if (empty($listManager->getOne('ListeOuiNonShop'))) {
            $listManager->create('OuiNonShop', $listeValues['labels']);
            return '<br/>✅ List "OuiNonShop" created !';
        }
        return '';
    }
}

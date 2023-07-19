<?php

namespace YesWiki\Shop;

use Configuration;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Security\Controller\SecurityController;

class UpdateHandler__ extends YesWikiHandler
{
    private const PATHS = [
        'forms' => [
            'Produit' => 'tools/shop/setup/forms/Form - Produit.txt',
        ],
    ];

    public function run()
    {
        if ($this->getService(SecurityController::class)->isWikiHibernated()) {
            throw new \Exception(_t('WIKI_IN_HIBERNATION'));
        };
        if (!$this->wiki->UserIsAdmin()) {
            return null;
        }

        $formIdsParam = $this->params->get('shop')['forms']['products'] ?? '';
        $productsFormIds = array_filter(array_map('trim', explode(',', $formIdsParam)));
        $formManager = $this->getService(FormManager::class);

        if (empty($productsFormIds)) {
            $formId = $this->createDefaultForm();
            $this->updateWakkaConfig($formId);
            return;
        }

        foreach ($productsFormIds as $productsFormId) {
            $this->getFormOrCreate($productsFormId);
        }

        return null;
    }

    /**
     * @param string $formId
     */
    private function getFormOrCreate(string $formId): void
    {
        // get services
        $formManager = $this->getService(FormManager::class);
        if (!empty($formId)) {
            // get Form
            $form = $formManager->getOne($formId);
            if (!empty($form)) {
                return;
            }
            $this->createDefaultForm($formId);
        }
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
            include_once 'tools/templates/libs/Configuration.php';
            $config = new Configuration('wakka.config.php');
            $config->load();

            $baseKey = 'shop';
            $tmp = isset($config->$baseKey) ? $config->$baseKey : [];
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
}
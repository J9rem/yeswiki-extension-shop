<?php

namespace YesWiki\Shop;

use Configuration;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Security\Controller\SecurityController;

class UpdateHandler__ extends YesWikiHandler
{
    private $formsIds;

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
        $this->formsIds = [];
        foreach (self::PATHS['forms'] as $formName => $filePath) {
            $this->formsIds[$formName] = $this->params->get('shop')['forms'][$formName.'_id'] ?? null;
        }

        // We get the param products list
        $lists = $this->params->get('shop')['lists'];

        foreach ($lists as $key => $value) {
            $this->getFormOrCreate($value);
        }

        return null;
    }

    /**
     * @param string $formName
     * @return null|string $formId
     */
    private function getFormOrCreate(string $formName): ?string
    {
        // get services
        $formManager = $this->getService(FormManager::class);
        // get Form Id
        $formId = $this->formsIds[$formName] ;

        // get Form
        if (!empty($formId) && intval($formId) === intval(strval($formId))) {
            $form = $formManager->getOne($formId);
        }

        //Change this code
        if (empty($form['bn_label_nature'])) {
            // create a form
            $formId = $formManager->findNewId();
            if (!$formManager->create([
                'bn_label_nature' => $formName,
                'bn_template'=> 'texte***bf_titre***Titre de la fiche*** *** *** *** ***text***1*** *** *** * *** * *** *** *** ***',
                'bn_description'=> '',
                'bn_sem_context'=> '',
                'bn_sem_type'=> '',
                'bn_condition'=> '',
                'bn_id_nature' => null,
            ])) {
                return null;
            };
        }
        // save id in wakka.config.php
        if (!empty($formId)) {
            include_once 'tools/templates/libs/Configuration.php';
            $config = new Configuration('wakka.config.php');
            $config->load();

            $baseKey = 'benevolat';
            $tmp = isset($config->$baseKey) ? $config->$baseKey : [];
            if (!isset($tmp['forms'])) {
                $tmp['forms'] = [];
            }

            $this->formsIds[$formName] = $formId;
            foreach ($this->formsIds as $formNameLocal => $formIdLocal) {
                $tmp['forms'][$formNameLocal.'_id'] = $formIdLocal;
            }

            $config->$baseKey = $tmp;
            $config->write();

            unset($config);
            return $formId;
        }

        return null;
    }

    private function updateForm(string $formName): ?string
    {
        // get services
        $formManager = $this->getService(FormManager::class);

        // get file
        if (!file_exists(self::PATHS['forms'][$formName])) {
            return $this->render('@templates/alert-message.twig', [
                'type' => 'danger',
                'message' => str_replace(
                    ['{formName}','{filePath}'],
                    [$formName,self::PATHS['forms'][$formName]],
                    _t('BENEVOLAT_UPDATE_FORM_ERROR')
                ),
            ]);
        }
        $formTemplate = file_get_contents(self::PATHS['forms'][$formName]);

        // get Form Id
        $formId = $this->getFormOrCreate($formName);
        if (empty($formId)) {
            return $this->render('@templates/alert-message.twig', [
                'type' => 'danger',
                'message' => str_replace(
                    ['{formName}'],
                    [$formName],
                    _t('BENEVOLAT_UPDATE_FORM_ERROR_CREATION')
                ),
            ]);
        }
        // get linked form Id
        $linkedFormName = $formName === 'Benevole' ? 'Benevolat' : 'Benevole';
        $linkedFormId = $this->getFormOrCreate($linkedFormName);
        if (empty($linkedFormId)) {
            return $this->render('@templates/alert-message.twig', [
                'type' => 'danger',
                'message' => str_replace(
                    ['{formName}'],
                    [$linkedFormName],
                    _t('BENEVOLAT_UPDATE_FORM_ERROR_CREATION')
                ),
            ]);
        }

        $creationProfilBenevoleUrl = $this->wiki->Href('', $this->pagesTags['CreationProfilBenevole']['new']);
        $ajoutBenevolatUrl = $this->wiki->Href('', $this->pagesTags['AjoutBenevolat']['new']);
        $suiviBenevolatUrl = $this->wiki->Href('', $this->pagesTags['SuiviBenevolat']['new']);
        $listeMois = $this->listsIds['ListeMois']['new'];
        $listeAnnees = $this->listsIds['ListeAnnees']['new'];

        $formTemplate = str_replace(
            ['{linkedFormId}','{creationProfilBenevoleUrl}','{ajoutBenevolatUrl}','{suiviBenevolatUrl}','{ListeMois}','{ListeAnnees}'],
            [$linkedFormId,$creationProfilBenevoleUrl,$ajoutBenevolatUrl,$suiviBenevolatUrl,$listeMois,$listeAnnees],
            $formTemplate
        );

        $formManager->update([
            'bn_label_nature' => $formName,
            'bn_template'=> $formTemplate,
            'bn_description'=> '',
            'bn_sem_context'=> '',
            'bn_sem_type'=> '',
            'bn_condition'=> '',
            'bn_id_nature'=> $formId,
        ]);

        return $this->render('@templates/alert-message.twig', [
            'type' => 'success',
            'message' => str_replace(
                ['{formName}'],
                [$formName],
                _t('BENEVOLAT_UPDATE_FORM_SUCCESS')
            ),
        ]);
    }
}

<?php

namespace YesWiki\Shop;

use Configuration;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\ListManager;
use YesWiki\Core\Service\LinkTracker;
use YesWiki\Core\Service\PageManager;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Security\Controller\SecurityController;

class GestionShopHandler extends YesWikiHandler
{
    private const PATHS = [
        'lists' => [
            'ListeFormsProduit' => 'tools/shop/setup/lists/ListeFormsProduit.json',
        ],
        'forms' => [
            'Produit' => 'tools/shop/setup/forms/Form - Produit.txt',
        ],
    ];

    private $formsIds ;
    private $pagesTags ;
    private $listsIds ;

    public function run(): string
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

        // init ids

        $this->listsIds = [];
        foreach (self::PATHS['lists'] as $listName => $filePath) {
            $this->listsIds[$listName] = [
                'new' => $this->params->get('shop')['lists'][$listName.'_id'] ?? $listName
            ];
        }
        $this->formsIds = [];
        foreach (self::PATHS['forms'] as $formName => $filePath) {
            $this->formsIds[$formName] = $this->params->get('shop')['forms'][$formName.'_id'] ?? null;
        }
        $this->pagesTags = [];
        foreach (self::PATHS['pages'] as $pageTag => $filePath) {
            $this->pagesTags[$pageTag] = [
                'new' => $this->params->get('shop')['pages'][$pageTag.'_tag'] ?? $pageTag
            ];
        }

        if (isset($_POST['valider'])) {
            $messages = $this->update();
        }

        // get services
        $listManager = $this->getService(ListManager::class);
        $formManager = $this->getService(FormManager::class);
        $pageManager = $this->getService(PageManager::class);

        // get Lists
        $listsTags = array_map(function ($listName) use ($listManager) {
            return [
                'new' => $listName['new'],
                'current' => empty($listManager->getOne($listName['new'])) ? null : $listName['new']
            ];
        }, $this->listsIds);

        // filter forms
        $this->formsIds = array_map(function ($id) use ($formManager) {
            return (empty($id) || empty($formManager->getOne($id))) ? null : $id;
        }, $this->formsIds);

        // get Pages
        $this->pagesTags = array_map(function ($pageTag) use ($pageManager) {
            return [
                'new' => $pageTag['new'],
                'current' => empty($pageManager->getOne($pageTag['new'])) ? null : $pageTag['new']
            ];
        }, $this->pagesTags);

        return $this->renderInSquelette('@benevolat/gestion-benevolat.twig', [
            'messages' => $messages ?? null,
            'listsTags' => $listsTags,
            'formsIds' => $this->formsIds,
            'pagesTags' => $this->pagesTags ,
        ]);
    }

    private function update()
    {
        $output = '';
        foreach (self::PATHS['lists'] as $listName => $filePath) {
            if (isset($_POST['update'.$listName]) && in_array($_POST['update'.$listName], [1,'1'], true)) {
                $name = (isset($_POST['updateName'.$listName]) && preg_match("/^".WN_CHAR."+$/m", $_POST['updateName'.$listName]))
                    ? $_POST['updateName'.$listName] : '' ;
                $output .= $this->updateList($listName, $name);
            }
        }
        foreach (self::PATHS['pages'] as $pageTag => $filePath) {
            if (isset($_POST['updatePage'.$pageTag]) && in_array($_POST['updatePage'.$pageTag], [1,'1'], true)) {
                $name = (isset($_POST['updateName'.$pageTag]) && preg_match("/^".WN_CAMEL_CASE_EVOLVED."$/m", $_POST['updateName'.$pageTag]))
                    ? $_POST['updateName'.$pageTag] : '' ;
                $output .= $this->updatePage($pageTag, $name);
            }
        }
        foreach (self::PATHS['forms'] as $formName => $filePath) {
            if (isset($_POST['updateForm'.$formName]) && in_array($_POST['updateForm'.$formName], [1,'1'], true)) {
                $output .= $this->updateForm($formName);
            }
        }
        if (isset($_POST['updatePageRapideHaut']) && in_array($_POST['updatePageRapideHaut'], [1,'1'], true)) {
            $output .= $this->updatePageRapideHaut();
        }
        return $output;
    }

    private function updateList(string $listName, string $name): ?string
    {
        // get services
        $listManager = $this->getService(ListManager::class);

        $name = empty($name) ? substr($listName, 5) : $name;
        $longName = 'Liste' . $name;

        // get file
        if (!file_exists(self::PATHS['lists'][$listName])) {
            return $this->render('@templates/alert-message.twig', [
                'type' => 'danger',
                'message' => str_replace(
                    ['{listName}','{filePath}'],
                    [$longName,self::PATHS['lists'][$listName]],
                    _t('BENEVOLAT_UPDATE_LIST_ERROR')
                ),
            ]);
        }
        $listeValues = json_decode(file_get_contents(self::PATHS['lists'][$listName]), true);

        // get Liste Id
        $listId = $this->listsIds[$listName]['new'] ?? $listName ;
        if ($listId !== $longName) {
            // save config

            include_once 'tools/templates/libs/Configuration.php';
            $config = new Configuration('wakka.config.php');
            $config->load();

            $baseKey = 'benevolat';
            $tmp = isset($config->$baseKey) ? $config->$baseKey : [];
            if ($longName !== $listName) {
                if (!isset($tmp['lists'])) {
                    $tmp['lists'] = [];
                }
                $tmp['lists'][$listName.'_id'] = $longName;
            } else {
                if (isset($tmp['lists'])) {
                    unset($tmp['lists'][$listName.'_id']);
                    if (empty($tmp['lists'])) {
                        unset($tmp['lists']);
                    }
                }
            }

            $config->$baseKey = $tmp;
            $config->write();
            $this->listsIds[$listName]['new'] = $longName;
            $listId = $longName;

            unset($config);
        }

        // get List
        $list = $listManager->getOne($listId);

        // save Liste
        if (empty($list)) {
            $listManager->create($name, $listeValues['label']);
        } else {
            $listManager->update($listId, $name, $listeValues['label']);
        }

        return $this->render('@templates/alert-message.twig', [
            'type' => 'success',
            'message' => str_replace(
                ['{listName}'],
                [$listId],
                _t('BENEVOLAT_UPDATE_LIST_SUCCESS')
            ),
        ]);
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

    private function updatePage(string $pageTag, string $newPageTag): ?string
    {
        // get services
        $pageManager = $this->getService(PageManager::class);
        $linkTracker = $this->getService(LinkTracker::class);

        $newPageTag = empty($newPageTag) ? $pageTag : $newPageTag;

        // get file
        if (!file_exists(self::PATHS['pages'][$pageTag])) {
            return $this->render('@templates/alert-message.twig', [
                'type' => 'danger',
                'message' => str_replace(
                    ['{pageTag}','{filePath}'],
                    [$newPageTag,self::PATHS['pages'][$pageTag]],
                    _t('BENEVOLAT_UPDATE_PAGE_ERROR')
                ),
            ]);
        }
        $pageContent = file_get_contents(self::PATHS['pages'][$pageTag]);

        switch ($pageTag) {
            case 'CreationProfilBenevole':
                $formId = $this->getFormOrCreate('Benevole');
                if (!empty($formId)) {
                    $anchor = '{formId}';
                    $replacement = $formId;
                }
                $suiviBenevolatTag = $this->pagesTags['SuiviBenevolat']['new'];
                break;
            case 'AjoutBenevolat':
                $suiviBenevolatTag = $this->pagesTags['SuiviBenevolat']['new'];
            // no break
            case 'SuiviBenevolat':
                $suiviBenevolatTag = $suiviBenevolatTag ?? $newPageTag;
                $formId = $this->getFormOrCreate('Benevolat');
                if (!empty($formId)) {
                    $anchor = '{formId}';
                    $replacement = $formId;
                }
                break;
            default:
                break;
        }

        if (!empty($anchor)) {
            $pageContent = str_replace(
                [$anchor,'{CreationProfilBenevole}','{AjoutBenevolat}','{SuiviBenevolat}'],
                [$replacement,$this->pagesTags['CreationProfilBenevole']['new'],$this->pagesTags['AjoutBenevolat']['new'],$suiviBenevolatTag],
                $pageContent
            );
        }

        // save page
        $pageSaved = ($pageManager->save($newPageTag, $pageContent) === 0);
        if ($pageSaved) {
            $page = $pageManager->getOne($newPageTag);
        }
        if (!$pageSaved || empty($page)) {
            return $this->render('@templates/alert-message.twig', [
                'type' => 'danger',
                'message' => str_replace(
                    ['{pageTag}'],
                    [$newPageTag],
                    _t('BENEVOLAT_UPDATE_SAVE_PAGE_ERROR')
                ),
            ]);
        }

        $linkTracker->registerLinks($page);

        $savedPageTag = $this->pagesTags[$pageTag]['new'] ?? $pageTag ;
        if ($newPageTag !== $savedPageTag) {
            // save config

            include_once 'tools/templates/libs/Configuration.php';
            $config = new Configuration('wakka.config.php');
            $config->load();

            $baseKey = 'benevolat';
            $tmp = isset($config->$baseKey) ? $config->$baseKey : [];
            if ($newPageTag !== $pageTag) {
                if (!isset($tmp['pages'])) {
                    $tmp['pages'] = [];
                }
                $tmp['pages'][$pageTag.'_tag'] = $newPageTag;
            } else {
                if (isset($tmp['pages'])) {
                    unset($tmp['pages'][$pageTag.'_tag']);
                    if (empty($tmp['pages'])) {
                        unset($tmp['pages']);
                    }
                }
            }

            $config->$baseKey = $tmp;
            $config->write();
            $this->pagesTags[$pageTag]['new'] = $newPageTag;
            $savedPageTag = $newPageTag;

            unset($config);
        }

        return $this->render('@templates/alert-message.twig', [
            'type' => 'success',
            'message' => str_replace(
                ['{pageTag}'],
                [$newPageTag],
                _t('BENEVOLAT_UPDATE_PAGE_SUCCESS')
            ),
        ]);
    }

    private function updatePageRapideHaut()
    {
        // get services
        $pageManager = $this->getService(PageManager::class);
        $linkTracker = $this->getService(LinkTracker::class);
        $pageRapideHaut = $pageManager->getOne('PageRapideHaut');

        if (empty($pageRapideHaut)) {
            return $this->render('@templates/alert-message.twig', [
                'type' => 'danger',
                'message' => _t('BENEVOLAT_UPDATE_PAGERAPIDEHAUT_NOT_FOUND'),
            ]);
        } else {


            // get file
            if (!file_exists(self::PATHS['PageRapideHaut'])) {
                return $this->render('@templates/alert-message.twig', [
                    'type' => 'danger',
                    'message' => str_replace(
                        ['{filePath}'],
                        [self::PATHS['PageRapideHaut']],
                        _t('BENEVOLAT_UPDATE_PAGERAPIDEHAUT_FILE_NOT_FOUND')
                    ),
                ]);
            }
            $newContent = file_get_contents(self::PATHS['PageRapideHaut']);
            $newContent = str_replace('{SuiviBenevolat}', $this->pagesTags['SuiviBenevolat']['new'], $newContent);

            $anchorBeginning = '{# Benevolat - start #}';
            $anchorEnd = '{# Benevolat - stop #}';
            $body = $pageRapideHaut['body'];
            $newContent = $anchorBeginning."\n".$newContent.$anchorEnd;
            $anchorBeginningRegExp = str_replace(['{','}'], ['\\{','\\}'], $anchorBeginning);
            $anchorEndRegExp = str_replace(['{','}'], ['\\{','\\}'], $anchorEnd);
            if (preg_match("/{$anchorBeginningRegExp}.*\s*.*\s*.*\s*.*{$anchorEndRegExp}/m", $body, $matches)) {
                $base = $matches[0];
                $body = str_replace($base, $newContent, $body);
            } elseif (preg_match("/link=\"GererSite\"\\}\\}/", $body, $matches)) {
                $base = $matches[0];
                $body = str_replace($base, $base.$newContent, $body);
            } elseif (preg_match("/\\s*\\{\\{end elem=\"buttondropdown\"\\}\\}/", $body, $matches)) {
                $base = $matches[0];
                $body = str_replace($base, $newContent."\r\n".$base, $body);
            } else {
                return $this->render('@templates/alert-message.twig', [
                    'type' => 'warning',
                    'message' => _t('BENEVOLAT_UPDATE_PAGERAPIDEHAUT_NOT_UPDATE'),
                ]);
            }

            // save page
            $pageSaved = ($pageManager->save($pageRapideHaut['tag'], $body) === 0);
            if ($pageSaved) {
                $page = $pageManager->getOne($pageRapideHaut['tag']);
            }
            if (!$pageSaved || empty($page)) {
                return $this->render('@templates/alert-message.twig', [
                    'type' => 'danger',
                    'message' => str_replace(
                        ['{pageTag}'],
                        ['PageRapideHaut'],
                        _t('BENEVOLAT_UPDATE_UPDATE_PAGE_ERROR')
                    ),
                ]);
            }
            $linkTracker->registerLinks($page);
            return $this->render('@templates/alert-message.twig', [
                'type' => 'success',
                'message' => _t('BENEVOLAT_UPDATE_PAGERAPIDEHAUT_SUCCESS'),
            ]);
        }

        return null;
    }
}

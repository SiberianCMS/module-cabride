<?php

namespace Cabride\Controller;

use Application_Controller_Default;

/**
 * Class Dashboard
 * @package Cabride\Controller
 */
class Dashboard extends Application_Controller_Default
{
    /**
     *
     */
    public function editAction()
    {
        $this->loadPartials();
        $layout = $this->getLayout();

        // Vars assigned to the view automatically!
        $contentPartial = $layout->getPartial('content');
        $this->assignVars($contentPartial);

        if ($layout->getPartial('content_editor')) {
            // Vars assigned to the view automatically!
            $contentEditorPartial = $layout->getPartial('content_editor');
            $this->assignVars($contentEditorPartial);
        }
    }
}

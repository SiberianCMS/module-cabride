<?php

/**
 * Class Cabride_Controller_Dashboard
 */
class Cabride_Controller_Dashboard extends Application_Controller_Default
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

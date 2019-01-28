<?php

namespace Azt3k\SS\Twig;

trait TwigRenderer {

    /**
     * [__get description]
     * @param  [type] $name [description]
     * @return [type]       [description]
     */
    public function __get($name) {

        if ($name == 'dic') {
            return $this->dic = new TwigContainer;
        } else {
            return parent::__get($name);
        }
    }

    /**
     * [__isset description]
     * @param  [type]  $name [description]
     * @return boolean       [description]
     */
    public function __isset($name) {

        return $this->hasMethod($name) ? false : true;
    }

    /**
     * Overrides the renderWith method for DOs
     * @param  [type] $templates    [description]
     * @param  [type] $customFields [description]
     * @return [type]               [description]
     */
    public function renderWith($templates, $customFields = null) {

        $data = ($this->customisedObject) ? $this->customisedObject : $this;

        if (is_array($customFields) || $customFields instanceof ViewableData) {
            $data = $data->customise($customFields);
        }

        if (!is_array($templates)) {
            $templates = [$templates];
        }

        try {
            return $this->renderTwig($templates, $data);
        } catch (\InvalidArgumentException $e) {
            return parent::renderWith($templates, $customFields);
        }

    }

    /**
     * [render description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function render($params = null) {

        $obj = ($this->customisedObj) ? $this->customisedObj : $this;
        if ($params) {
            $obj = $this->customise($params);
        }

        $action = method_exists($this, 'getAction')
            ? $this->getAction()
            : null;

        return $this->renderTwig(
            $this->getTemplateList($action),
            $obj
        );
    }

    protected function renderTwig($templates, $context) {
        return $this->getTwigTemplate($templates)->render([
            $this->dic['twig.controller_variable_name'] => $context
        ]);
    }

    public function customise($params) {

        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    protected function getTwigTemplate($templates) {

        $loader = $this->dic['twig.loader'];
        $extensions = $this->dic['twig.extensions'];

        foreach ($templates as $value) {

            // catches scenarios when a template is supplied as:
            // Array
            // (
            //     [type] => Includes
            //     [0] => SilverStripe\Security\Security_login
            // )
            if (is_array($value)) $value = $value[0];

            foreach ($extensions as $extension) {

                if ($loader->exists($value . $extension)) {
                    return $this->dic['twig']->loadTemplate($value . $extension);
                }
            }
        }
        throw new \InvalidArgumentException("No templates for " . print_r($templates, 1) . " exist");
    }

    /**
     * [buildTemplatesFromClassName description]
     * @param  [type] $className [description]
     * @param  [type] $action    [description]
     * @return [type]            [description]
     */
    protected function buildTemplatesFromClassName($className, $action = null) {

        // init templates
        $templates = [];

        // Add action-specific templates for inheritance chain
        if ($action && $action != 'index') {
            $parentClass = $className;
            while (
                $parentClass &&
                $parentClass != 'SilverStripe\Control\Controller' &&
                $parentClass != 'SilverStripe\ORM\DataObject'
            ) {
                $classParts = explode('\\', $parentClass);
                $templates[] = $classParts[count($classParts) - 1] . '_' . $action;
                $parentClass = get_parent_class($parentClass);
            }
        }

        // Add controller templates for inheritance chain
        $parentClass = $className;
        while (
            $parentClass &&
            $parentClass != 'SilverStripe\Control\Controller' &&
            $parentClass != 'SilverStripe\ORM\DataObject'
        ) {
            $classParts = explode('\\', $parentClass);
            $templates[] = $classParts[count($classParts) - 1];
            $parentClass = get_parent_class($parentClass);
        }

        return $templates;
    }

    /**
     * [getTemplateList description]
     * @param  [type] $action [description]
     * @return [type]         [description]
     */
    protected function getTemplateList($action = null) {

        // Hard-coded templates
        if (!empty($this->templates[$action])) {
            $templates = $this->templates[$action];
        } elseif (!empty($this->templates['index'])) {
            $templates = $this->templates['index'];
        } elseif (!empty($this->template)) {
            $templates = $this->template;
        } else {

            // build template list
            // get_class and $this->className return different things sometimes
            $templates = array_unique(array_merge(
                $this->buildTemplatesFromClassName(get_class($this), $action),
                $this->buildTemplatesFromClassName($this->ClassName, $action)
            ));
        }

        return $templates;
    }

}

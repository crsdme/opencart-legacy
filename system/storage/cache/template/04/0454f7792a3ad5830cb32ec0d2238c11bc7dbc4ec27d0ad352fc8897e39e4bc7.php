<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* tailwind/components/button.twig */
class __TwigTemplate_30b7637179f4820b4d02112d9ae7b41f9f2c34d7e704270ce8c2dca2e6d54f35 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<button class=\"button-primary\">Button</button>

<button class=\"button-secondary\">Button</button>

<button class=\"button-destructive\">Button</button>

<button class=\"button-secondary icon\">
    <svg
        xmlns=\"http://www.w3.org/2000/svg\"
        fill=\"none\"
        viewBox=\"0 0 24 24\"
        stroke-width=\"1.5\"
        stroke=\"currentColor\"
        class=\"size-6\"
    >
        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M12 4.5v15m7.5-7.5h-15\" />
    </svg>
</button>

<button class=\"button-primary icon\">
    <svg
        xmlns=\"http://www.w3.org/2000/svg\"
        fill=\"none\"
        viewBox=\"0 0 24 24\"
        stroke-width=\"1.5\"
        stroke=\"currentColor\"
        class=\"size-6\"
    >
        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M12 4.5v15m7.5-7.5h-15\" />
    </svg>
</button>

<button class=\"button-destructive icon\">
    <svg
        xmlns=\"http://www.w3.org/2000/svg\"
        fill=\"none\"
        viewBox=\"0 0 24 24\"
        stroke-width=\"1.5\"
        stroke=\"currentColor\"
        class=\"size-6\"
    >
        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M12 4.5v15m7.5-7.5h-15\" />
    </svg>
</button>
";
    }

    public function getTemplateName()
    {
        return "tailwind/components/button.twig";
    }

    public function getDebugInfo()
    {
        return array (  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/components/button.twig", "/var/www/html/catalog/view/theme/tailwind/components/button.twig");
    }
}

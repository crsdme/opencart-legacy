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

/* tailwind/template/common/layout.twig */
class __TwigTemplate_5828904bf63a49528759843827db9800c41395b2c1d17ed674a9e36cc9316fff extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<!DOCTYPE html>
<html dir=\"";
        // line 2
        echo ($context["direction"] ?? null);
        echo "\" lang=\"";
        echo ($context["lang"] ?? null);
        echo "\">
    <head>
        ";
        // line 4
        echo ($context["head"] ?? null);
        echo "
    </head>
    <body>
        ";
        // line 7
        echo ($context["header"] ?? null);
        echo "

        ";
        // line 9
        $this->displayBlock('content', $context, $blocks);
        // line 12
        echo "
        ";
        // line 13
        echo ($context["footer"] ?? null);
        echo "
    </body>
</html>
";
    }

    // line 9
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 10
        echo "
        ";
    }

    public function getTemplateName()
    {
        return "tailwind/template/common/layout.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  76 => 10,  72 => 9,  64 => 13,  61 => 12,  59 => 9,  54 => 7,  48 => 4,  41 => 2,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/common/layout.twig", "/var/www/html/catalog/view/theme/tailwind/template/common/layout.twig");
    }
}

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
class __TwigTemplate_8db181251f7b560796c3d9d112dc763e4d3d072bdb784a081e9aac65976edd12 extends \Twig\Template
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
        echo "<!DOCTYPE html>
<html dir=\"";
        // line 2
        echo ($context["direction"] ?? null);
        echo "\" lang=\"";
        echo ($context["lang"] ?? null);
        echo "\" class=\"";
        echo ($context["theme"] ?? null);
        echo "\">
    <head>
        ";
        // line 4
        echo ($context["head"] ?? null);
        echo "
        ";
        // line 5
        echo twig_get_attribute($this->env, $this->source, ($context["microdata"] ?? null), "head", [], "any", false, false, false, 5);
        echo "
    </head>
    <body>
        ";
        // line 8
        echo ($context["header"] ?? null);
        echo "

        ";
        // line 10
        echo ($context["content"] ?? null);
        echo "

        ";
        // line 12
        echo ($context["footer"] ?? null);
        echo "

        ";
        // line 14
        echo twig_get_attribute($this->env, $this->source, ($context["microdata"] ?? null), "body", [], "any", false, false, false, 14);
        echo "
    </body>
</html>
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
        return array (  74 => 14,  69 => 12,  64 => 10,  59 => 8,  53 => 5,  49 => 4,  40 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/common/layout.twig", "");
    }
}

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

/* tailwind/template/error/not_found.twig */
class __TwigTemplate_c6ec0eb1344f01299388a7e6c1af457d2c22bce99462c84be55901ebcb8aeadb extends \Twig\Template
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
        $context["class"] = "col-span-12 ";
        // line 2
        if ((($context["column_left"] ?? null) && ($context["column_right"] ?? null))) {
            // line 3
            echo "    ";
            $context["class"] = (($context["class"] ?? null) . "lg:col-span-6");
        } elseif ((        // line 4
($context["column_left"] ?? null) || ($context["column_right"] ?? null))) {
            // line 5
            echo "    ";
            $context["class"] = (($context["class"] ?? null) . "lg:col-span-9");
        } else {
            // line 7
            echo "    ";
            $context["class"] = (($context["class"] ?? null) . "lg:col-span-12");
        }
        // line 9
        echo "
<div class=\"container mx-auto px-2\">
    ";
        // line 11
        $this->loadTemplate("tailwind/components/breadcrumbs.twig", "tailwind/template/error/not_found.twig", 11)->display($context);
        // line 12
        echo "</div>
<div class=\"container mx-auto px-2 grid grid-cols-1 lg:grid-cols-12 gap-6\">
    ";
        // line 14
        echo ($context["column_left"] ?? null);
        echo "
    <main class=\"";
        // line 15
        echo ($context["class"] ?? null);
        echo "\">
        ";
        // line 16
        echo ($context["content_top"] ?? null);
        echo "

        <div class=\"text-block mx-auto my-10 w-full md:w-1/2\">
            <h1 class=\"heading\">";
        // line 19
        echo ($context["heading_title"] ?? null);
        echo "</h1>
            <p>
                ";
        // line 21
        echo ($context["text_error"] ?? null);
        echo "
            </p>
            <a href=\"";
        // line 23
        echo ($context["continue"] ?? null);
        echo "\" class=\"button button-primary\">";
        echo ($context["button_continue"] ?? null);
        echo "</a>
        </div>

        ";
        // line 26
        echo ($context["content_bottom"] ?? null);
        echo "
    </main>
    ";
        // line 28
        echo ($context["column_right"] ?? null);
        echo "
</div>
";
    }

    public function getTemplateName()
    {
        return "tailwind/template/error/not_found.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  101 => 28,  96 => 26,  88 => 23,  83 => 21,  78 => 19,  72 => 16,  68 => 15,  64 => 14,  60 => 12,  58 => 11,  54 => 9,  50 => 7,  46 => 5,  44 => 4,  41 => 3,  39 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/error/not_found.twig", "");
    }
}

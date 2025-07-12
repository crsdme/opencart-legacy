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

/* tailwind/template/common/home.twig */
class __TwigTemplate_0b30612254a04553e53fa3ddd0cc8d6ec87cb091b723d5020a729a2a06971e9d extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "tailwind/template/common/layout.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        $this->parent = $this->loadTemplate("tailwind/template/common/layout.twig", "tailwind/template/common/home.twig", 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        echo "    <div id=\"common-home\" class=\"container\">
        <div class=\"row\">
            ";
        // line 6
        echo ($context["column_left"] ?? null);
        echo "
            ";
        // line 7
        if ((($context["column_left"] ?? null) && ($context["column_right"] ?? null))) {
            // line 8
            echo "                ";
            $context["class"] = "col-sm-6";
            // line 9
            echo "            ";
        } elseif ((($context["column_left"] ?? null) || ($context["column_right"] ?? null))) {
            // line 10
            echo "                ";
            $context["class"] = "col-sm-9";
            // line 11
            echo "            ";
        } else {
            // line 12
            echo "                ";
            $context["class"] = "col-sm-12";
            // line 13
            echo "            ";
        }
        // line 14
        echo "            <div id=\"content\" class=\"";
        echo ($context["class"] ?? null);
        echo "\">
                ";
        // line 15
        echo ($context["content_top"] ?? null);
        echo ($context["content_bottom"] ?? null);
        echo "
            </div>
            ";
        // line 17
        echo ($context["column_right"] ?? null);
        echo "
        </div>
    </div>
";
    }

    public function getTemplateName()
    {
        return "tailwind/template/common/home.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  89 => 17,  83 => 15,  78 => 14,  75 => 13,  72 => 12,  69 => 11,  66 => 10,  63 => 9,  60 => 8,  58 => 7,  54 => 6,  50 => 4,  46 => 3,  35 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/common/home.twig", "");
    }
}

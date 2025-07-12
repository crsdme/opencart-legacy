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

/* tailwind/template/common/search.twig */
class __TwigTemplate_759b5fb55d0a0b165b841b438bb0077f38acd5cb59b2c649dc7702559107d48c extends \Twig\Template
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
        echo "<div id=\"search\" class=\"input-group\">
    <input
        type=\"text\"
        name=\"search\"
        value=\"";
        // line 5
        echo ($context["search"] ?? null);
        echo "\"
        placeholder=\"";
        // line 6
        echo ($context["text_search"] ?? null);
        echo "\"
        class=\"form-control input-lg\"
    />
    <span class=\"input-group-btn\">
        <button type=\"button\" class=\"btn btn-default btn-lg\"><i class=\"fa fa-search\"></i></button>
    </span>
</div>
";
    }

    public function getTemplateName()
    {
        return "tailwind/template/common/search.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  47 => 6,  43 => 5,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/common/search.twig", "");
    }
}

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

/* tailwind/template/common/currency.twig */
class __TwigTemplate_2a5ce64dcf37588d85ab9f2699222e6b108dc1f6214de41e71c9dcb40dfcd959 extends \Twig\Template
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
        if ((twig_length_filter($this->env, ($context["currencies"] ?? null)) > 1)) {
            // line 2
            echo "    <div class=\"dropdown w-full flex-1\" data-align=\"center\">
        <form action=\"";
            // line 3
            echo ($context["action"] ?? null);
            echo "\" method=\"post\" enctype=\"multipart/form-data\" id=\"form-currency\">
            <p id=\"dropdownButton\" class=\"dropdown-button\">
                ";
            // line 5
            echo ($context["text_currency"] ?? null);
            echo "
            </p>

            <div id=\"dropdownMenu\" class=\"dropdown-menu\">
                ";
            // line 9
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["currencies"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["currency"]) {
                // line 10
                echo "                    ";
                if (twig_get_attribute($this->env, $this->source, $context["currency"], "symbol_left", [], "any", false, false, false, 10)) {
                    // line 11
                    echo "                        <button class=\"dropdown-item\" type=\"button\" name=\"";
                    echo twig_get_attribute($this->env, $this->source, $context["currency"], "code", [], "any", false, false, false, 11);
                    echo "\">
                            ";
                    // line 12
                    echo twig_get_attribute($this->env, $this->source, $context["currency"], "symbol_left", [], "any", false, false, false, 12);
                    echo " ";
                    echo twig_get_attribute($this->env, $this->source, $context["currency"], "title", [], "any", false, false, false, 12);
                    echo "
                        </button>
                    ";
                } else {
                    // line 15
                    echo "                        <button class=\"dropdown-item\" type=\"button\" name=\"";
                    echo twig_get_attribute($this->env, $this->source, $context["currency"], "code", [], "any", false, false, false, 15);
                    echo "\">
                            ";
                    // line 16
                    echo twig_get_attribute($this->env, $this->source, $context["currency"], "title", [], "any", false, false, false, 16);
                    echo " ";
                    echo twig_get_attribute($this->env, $this->source, $context["currency"], "symbol_right", [], "any", false, false, false, 16);
                    echo "
                        </button>
                    ";
                }
                // line 19
                echo "                ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['currency'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 20
            echo "            </div>
            <input type=\"hidden\" name=\"code\" value=\"\" />
            <input type=\"hidden\" name=\"redirect\" value=\"";
            // line 22
            echo ($context["redirect"] ?? null);
            echo "\" />
        </form>
    </div>
";
        }
    }

    public function getTemplateName()
    {
        return "tailwind/template/common/currency.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  97 => 22,  93 => 20,  87 => 19,  79 => 16,  74 => 15,  66 => 12,  61 => 11,  58 => 10,  54 => 9,  47 => 5,  42 => 3,  39 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/common/currency.twig", "");
    }
}

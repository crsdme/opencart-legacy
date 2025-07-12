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

/* tailwind/template/common/language.twig */
class __TwigTemplate_5b438b99f0abdec98497e2f03792a8a63e976d523be06759d575f84d0ad0c9ff extends \Twig\Template
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
        if ((twig_length_filter($this->env, ($context["languages"] ?? null)) > 1)) {
            // line 2
            echo "    <div class=\"pull-left\">
        <form action=\"";
            // line 3
            echo ($context["action"] ?? null);
            echo "\" method=\"post\" enctype=\"multipart/form-data\" id=\"form-language\">
            <div class=\"btn-group\">
                <button class=\"btn btn-link dropdown-toggle\" data-toggle=\"dropdown\">
                    ";
            // line 6
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["languages"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["language"]) {
                // line 7
                echo "                        ";
                if ((twig_get_attribute($this->env, $this->source, $context["language"], "code", [], "any", false, false, false, 7) == ($context["code"] ?? null))) {
                    // line 8
                    echo "                            <img
                                src=\"catalog/language/";
                    // line 9
                    echo twig_get_attribute($this->env, $this->source, $context["language"], "code", [], "any", false, false, false, 9);
                    echo "/";
                    echo twig_get_attribute($this->env, $this->source, $context["language"], "code", [], "any", false, false, false, 9);
                    echo ".png\"
                                alt=\"";
                    // line 10
                    echo twig_get_attribute($this->env, $this->source, $context["language"], "name", [], "any", false, false, false, 10);
                    echo "\"
                                title=\"";
                    // line 11
                    echo twig_get_attribute($this->env, $this->source, $context["language"], "name", [], "any", false, false, false, 11);
                    echo "\"
                            />
                        ";
                }
                // line 14
                echo "                    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['language'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 15
            echo "                    <span class=\"hidden-xs hidden-sm hidden-md\">";
            echo ($context["text_language"] ?? null);
            echo "</span>&nbsp;<i class=\"fa fa-caret-down\"></i>
                </button>
                <ul class=\"dropdown-menu\">
                    ";
            // line 18
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["languages"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["language"]) {
                // line 19
                echo "                        <li>
                            <button
                                class=\"btn btn-link btn-block language-select\"
                                type=\"button\"
                                name=\"";
                // line 23
                echo twig_get_attribute($this->env, $this->source, $context["language"], "code", [], "any", false, false, false, 23);
                echo "\"
                            >
                                <img
                                    src=\"catalog/language/";
                // line 26
                echo twig_get_attribute($this->env, $this->source, $context["language"], "code", [], "any", false, false, false, 26);
                echo "/";
                echo twig_get_attribute($this->env, $this->source, $context["language"], "code", [], "any", false, false, false, 26);
                echo ".png\"
                                    alt=\"";
                // line 27
                echo twig_get_attribute($this->env, $this->source, $context["language"], "name", [], "any", false, false, false, 27);
                echo "\"
                                    title=\"";
                // line 28
                echo twig_get_attribute($this->env, $this->source, $context["language"], "name", [], "any", false, false, false, 28);
                echo "\"
                                />
                                ";
                // line 30
                echo twig_get_attribute($this->env, $this->source, $context["language"], "name", [], "any", false, false, false, 30);
                echo "
                            </button>
                        </li>
                    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['language'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 34
            echo "                </ul>
            </div>
            <input type=\"hidden\" name=\"code\" value=\"\" />
            <input type=\"hidden\" name=\"redirect\" value=\"";
            // line 37
            echo ($context["redirect"] ?? null);
            echo "\" />
        </form>
    </div>
";
        }
    }

    public function getTemplateName()
    {
        return "tailwind/template/common/language.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  133 => 37,  128 => 34,  118 => 30,  113 => 28,  109 => 27,  103 => 26,  97 => 23,  91 => 19,  87 => 18,  80 => 15,  74 => 14,  68 => 11,  64 => 10,  58 => 9,  55 => 8,  52 => 7,  48 => 6,  42 => 3,  39 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/common/language.twig", "");
    }
}

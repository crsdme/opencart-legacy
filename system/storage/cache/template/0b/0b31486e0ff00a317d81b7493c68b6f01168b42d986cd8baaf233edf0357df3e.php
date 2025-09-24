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
class __TwigTemplate_209494e98bf468b5d1f1e6a4110076ce38ae86cf4b40f31bb7a653db9ecfdf70 extends \Twig\Template
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
            echo "<div class=\"dropdown w-full flex-1\" data-align=\"center\">
  <form action=\"";
            // line 3
            echo ($context["action"] ?? null);
            echo "\" method=\"post\" enctype=\"multipart/form-data\" id=\"form-language\">
      <p id=\"dropdownButton\" class=\"dropdown-button\">
        ";
            // line 5
            echo ($context["text_language"] ?? null);
            echo "
      </p>
        <div id=\"dropdownMenu\" class=\"dropdown-menu\">
            ";
            // line 8
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["languages"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["language"]) {
                // line 9
                echo "            <button class=\"dropdown-item\" ";
                if ((twig_get_attribute($this->env, $this->source, $context["language"], "code", [], "any", false, false, false, 9) == ($context["code"] ?? null))) {
                    echo "selected";
                }
                echo " onclick=\"location = '";
                echo twig_get_attribute($this->env, $this->source, $context["language"], "href", [], "any", false, false, false, 9);
                echo "'\">";
                echo twig_get_attribute($this->env, $this->source, $context["language"], "name", [], "any", false, false, false, 9);
                echo "</button>
            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['language'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 11
            echo "        </div>
        <input type=\"hidden\" name=\"code\" value=\"\" />
        <input type=\"hidden\" name=\"redirect\" value=\"";
            // line 13
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
        return array (  76 => 13,  72 => 11,  57 => 9,  53 => 8,  47 => 5,  42 => 3,  39 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/common/language.twig", "");
    }
}

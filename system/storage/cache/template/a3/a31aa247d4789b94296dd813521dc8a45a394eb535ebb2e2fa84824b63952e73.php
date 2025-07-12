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

/* tailwind/template/extension/module/blog_latest.twig */
class __TwigTemplate_8d123cea825cd7927224ec3271dff9acfcfaaf731b4e9276318c6897799637b1 extends \Twig\Template
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
        echo "<h3>";
        echo ($context["heading_title"] ?? null);
        echo "</h3>
<div class=\"row\">
 ";
        // line 3
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["articles"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["article"]) {
            // line 4
            echo "  <div class=\"product-layout col-lg-3 col-md-3 col-sm-6 col-xs-12\">
    <div class=\"product-thumb transition\">
      <div class=\"image\"><a href=\"";
            // line 6
            echo twig_get_attribute($this->env, $this->source, $context["article"], "href", [], "any", false, false, false, 6);
            echo "\"><img src=\"";
            echo twig_get_attribute($this->env, $this->source, $context["article"], "thumb", [], "any", false, false, false, 6);
            echo "\" alt=\"";
            echo twig_get_attribute($this->env, $this->source, $context["article"], "name", [], "any", false, false, false, 6);
            echo "\" title=\"";
            echo twig_get_attribute($this->env, $this->source, $context["article"], "name", [], "any", false, false, false, 6);
            echo "\" class=\"img-responsive\" /></a></div>
      <div class=\"caption\">
        <h4><a href=\"";
            // line 8
            echo twig_get_attribute($this->env, $this->source, $context["article"], "href", [], "any", false, false, false, 8);
            echo "\">";
            echo twig_get_attribute($this->env, $this->source, $context["article"], "name", [], "any", false, false, false, 8);
            echo "</a></h4>
        <p>";
            // line 9
            echo twig_get_attribute($this->env, $this->source, $context["article"], "description", [], "any", false, false, false, 9);
            echo "</p>
        ";
            // line 10
            if (twig_get_attribute($this->env, $this->source, $context["article"], "rating", [], "any", false, false, false, 10)) {
                // line 11
                echo "        <div class=\"rating\">
          ";
                // line 12
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(5);
                foreach ($context['_seq'] as $context["_key"] => $context["i"]) {
                    // line 13
                    echo "          ";
                    if ((twig_get_attribute($this->env, $this->source, $context["article"], "rating", [], "any", false, false, false, 13) < $context["i"])) {
                        // line 14
                        echo "          <span class=\"fa fa-stack\"><i class=\"fa fa-star-o fa-stack-2x\"></i></span>
          ";
                    } else {
                        // line 16
                        echo "          <span class=\"fa fa-stack\"><i class=\"fa fa-star fa-stack-2x\"></i><i class=\"fa fa-star-o fa-stack-2x\"></i></span>
          ";
                    }
                    // line 18
                    echo "          ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['i'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 19
                echo "        </div>
        ";
            }
            // line 21
            echo "      </div>
\t  <div class=\"button-group\">
        <button type=\"button\" onclick=\"location.href = ('";
            // line 23
            echo twig_get_attribute($this->env, $this->source, $context["article"], "href", [], "any", false, false, false, 23);
            echo "');\"><i class=\"fa fa-share\"></i> <span class=\"hidden-xs hidden-sm hidden-md\">";
            echo ($context["button_more"] ?? null);
            echo "</span></button>
        <button type=\"button\" data-toggle=\"tooltip\" title=\"";
            // line 24
            echo twig_get_attribute($this->env, $this->source, $context["article"], "date_added", [], "any", false, false, false, 24);
            echo "\" \"><i class=\"fa fa-clock-o\"></i></button>
        <button type=\"button\" data-toggle=\"tooltip\" title=\"";
            // line 25
            echo ($context["text_views"] ?? null);
            echo " ";
            echo twig_get_attribute($this->env, $this->source, $context["article"], "viewed", [], "any", false, false, false, 25);
            echo "\" \"><i class=\"fa fa-eye\"></i></button>
     </div>
    </div>
  </div>
  ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['article'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 30
        echo "</div>
";
    }

    public function getTemplateName()
    {
        return "tailwind/template/extension/module/blog_latest.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  129 => 30,  116 => 25,  112 => 24,  106 => 23,  102 => 21,  98 => 19,  92 => 18,  88 => 16,  84 => 14,  81 => 13,  77 => 12,  74 => 11,  72 => 10,  68 => 9,  62 => 8,  51 => 6,  47 => 4,  43 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/extension/module/blog_latest.twig", "");
    }
}

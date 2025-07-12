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

/* tailwind/template/extension/module/featured.twig */
class __TwigTemplate_b39385d81df250e87d4781bd67b5e4ca0fb5689d87835c6366bf480079d88810 extends \Twig\Template
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
        $context['_seq'] = twig_ensure_traversable(($context["products"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["product"]) {
            // line 4
            echo "        <div class=\"product-layout col-lg-3 col-md-3 col-sm-6 col-xs-12\">
            <div class=\"product-thumb transition\">
                <div class=\"image\">
                    <a href=\"";
            // line 7
            echo twig_get_attribute($this->env, $this->source, $context["product"], "href", [], "any", false, false, false, 7);
            echo "\">
                        <img
                            src=\"";
            // line 9
            echo twig_get_attribute($this->env, $this->source, $context["product"], "thumb", [], "any", false, false, false, 9);
            echo "\"
                            alt=\"";
            // line 10
            echo twig_get_attribute($this->env, $this->source, $context["product"], "name", [], "any", false, false, false, 10);
            echo "\"
                            title=\"";
            // line 11
            echo twig_get_attribute($this->env, $this->source, $context["product"], "name", [], "any", false, false, false, 11);
            echo "\"
                            class=\"img-responsive\"
                        />
                    </a>
                </div>
                <div class=\"caption\">
                    <h4><a href=\"";
            // line 17
            echo twig_get_attribute($this->env, $this->source, $context["product"], "href", [], "any", false, false, false, 17);
            echo "\">";
            echo twig_get_attribute($this->env, $this->source, $context["product"], "name", [], "any", false, false, false, 17);
            echo "</a></h4>
                    <p>
                        ";
            // line 19
            echo twig_get_attribute($this->env, $this->source, $context["product"], "description", [], "any", false, false, false, 19);
            echo "
                    </p>
                    ";
            // line 21
            if (twig_get_attribute($this->env, $this->source, $context["product"], "rating", [], "any", false, false, false, 21)) {
                // line 22
                echo "                        <div class=\"rating\">
                            ";
                // line 23
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(5);
                foreach ($context['_seq'] as $context["_key"] => $context["i"]) {
                    // line 24
                    echo "                                ";
                    if ((twig_get_attribute($this->env, $this->source, $context["product"], "rating", [], "any", false, false, false, 24) < $context["i"])) {
                        // line 25
                        echo "                                    <span class=\"fa fa-stack\"><i class=\"fa fa-star-o fa-stack-2x\"></i></span>
                                ";
                    } else {
                        // line 27
                        echo "                                    <span class=\"fa fa-stack\">
                                        <i class=\"fa fa-star fa-stack-2x\"></i><i class=\"fa fa-star-o fa-stack-2x\"></i>
                                    </span>
                                ";
                    }
                    // line 31
                    echo "                            ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['i'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 32
                echo "                        </div>
                    ";
            }
            // line 34
            echo "                    ";
            if (twig_get_attribute($this->env, $this->source, $context["product"], "price", [], "any", false, false, false, 34)) {
                // line 35
                echo "                        <p class=\"price\">
                            ";
                // line 36
                if ( !twig_get_attribute($this->env, $this->source, $context["product"], "special", [], "any", false, false, false, 36)) {
                    // line 37
                    echo "                                ";
                    echo twig_get_attribute($this->env, $this->source, $context["product"], "price", [], "any", false, false, false, 37);
                    echo "
                            ";
                } else {
                    // line 39
                    echo "                                <span class=\"price-new\">";
                    echo twig_get_attribute($this->env, $this->source, $context["product"], "special", [], "any", false, false, false, 39);
                    echo "</span>
                                <span class=\"price-old\">";
                    // line 40
                    echo twig_get_attribute($this->env, $this->source, $context["product"], "price", [], "any", false, false, false, 40);
                    echo "</span>
                            ";
                }
                // line 42
                echo "                            ";
                if (twig_get_attribute($this->env, $this->source, $context["product"], "tax", [], "any", false, false, false, 42)) {
                    // line 43
                    echo "                                <span class=\"price-tax\">";
                    echo ($context["text_tax"] ?? null);
                    echo " ";
                    echo twig_get_attribute($this->env, $this->source, $context["product"], "tax", [], "any", false, false, false, 43);
                    echo "</span>
                            ";
                }
                // line 45
                echo "                        </p>
                    ";
            }
            // line 47
            echo "                </div>
                <div class=\"button-group\">
                    <button type=\"button\" onclick=\"cart.add('";
            // line 49
            echo twig_get_attribute($this->env, $this->source, $context["product"], "product_id", [], "any", false, false, false, 49);
            echo "');\">
                        <i class=\"fa fa-shopping-cart\"></i>
                        <span class=\"hidden-xs hidden-sm hidden-md\">";
            // line 51
            echo ($context["button_cart"] ?? null);
            echo "</span>
                    </button>
                    <button
                        type=\"button\"
                        data-toggle=\"tooltip\"
                        title=\"";
            // line 56
            echo ($context["button_wishlist"] ?? null);
            echo "\"
                        onclick=\"wishlist.add('";
            // line 57
            echo twig_get_attribute($this->env, $this->source, $context["product"], "product_id", [], "any", false, false, false, 57);
            echo "');\"
                    >
                        <i class=\"fa fa-heart\"></i>
                    </button>
                    <button
                        type=\"button\"
                        data-toggle=\"tooltip\"
                        title=\"";
            // line 64
            echo ($context["button_compare"] ?? null);
            echo "\"
                        onclick=\"compare.add('";
            // line 65
            echo twig_get_attribute($this->env, $this->source, $context["product"], "product_id", [], "any", false, false, false, 65);
            echo "');\"
                    >
                        <i class=\"fa fa-exchange\"></i>
                    </button>
                </div>
            </div>
        </div>
    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['product'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 73
        echo "</div>
";
    }

    public function getTemplateName()
    {
        return "tailwind/template/extension/module/featured.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  206 => 73,  192 => 65,  188 => 64,  178 => 57,  174 => 56,  166 => 51,  161 => 49,  157 => 47,  153 => 45,  145 => 43,  142 => 42,  137 => 40,  132 => 39,  126 => 37,  124 => 36,  121 => 35,  118 => 34,  114 => 32,  108 => 31,  102 => 27,  98 => 25,  95 => 24,  91 => 23,  88 => 22,  86 => 21,  81 => 19,  74 => 17,  65 => 11,  61 => 10,  57 => 9,  52 => 7,  47 => 4,  43 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/extension/module/featured.twig", "");
    }
}

<?php
class ControllerDocsComponents extends Controller
{
	public function index()
	{
		$sections = $this->prepareSections($this->sections());

		$groups = [];

		foreach ($sections as $section) {
			$group = $section['group'];

			if (!isset($groups[$group])) {
				$groups[$group] = [
					'title' => $group,
					'items' => [],
				];
			}

			$groups[$group]['items'][] = [
				'title' => $section['title'],
				'href' => '#' . $section['id'],
				'active' => false,
			];
		}

		$data['title'] = 'Components';
		$data['page'] = 'components';
		$data['view'] = 'docs/components';
		$data['toc'] = [];
		$data['groups'] = array_values($groups);
		$data['sections'] = $sections;
		$data['products'] = $this->getProducts();
		$data['articles'] = $this->getArticles();
		$data['categories'] = $this->getCategories();
		$data['breadcrumbs'] = $this->getBreadcrumbs();
		$data['carousel_slides'] = $this->getCarouselSlides();
		$data['gallery'] = $this->getGallery($data['products']);
		$data['options'] = $this->sampleOptions();
		$data['recurrings'] = $this->sampleRecurrings();
		$data['custom_fields'] = $this->sampleCustomFields();
		$data['faq'] = $this->sampleFaq();
		$data['button_more'] = 'More';
		$data['text_option'] = 'Available Options';
		$data['text_select'] = '--- Please Select ---';
		$data['text_payment_recurring'] = 'Payment Profile';
		$data['button_upload'] = 'Upload';
		$data['heading_title'] = 'Sample product';
		$data['pagination_url'] = str_replace(
			['%7Bpage%7D', '%7bpage%7d'],
			'{page}',
			$this->url->link('docs/components', 'page={page}')
		);

		$this->response->setOutput($this->load->controller('docs/layout', $data));
	}

	private function includeOf($file, $with = '')
	{
		$include = '{% include "default/' . $file . '" %}';

		if ($with !== '') {
			$include = '{% include "default/' . $file . '" with ' . $with . ' %}';
		}

		return $include;
	}

	private function param($name, $type, $required, $text, $default = '')
	{
		return [
			'name' => $name,
			'type' => $type,
			'required' => (bool) $required,
			'default' => $default,
			'text' => $text,
		];
	}

	private function prepareSections(array $sections)
	{
		foreach ($sections as $id => $section) {
			$file = $section['file'];
			$include = !empty($section['include']) ? $section['include'] : $this->includeOf($file);
			$markup = isset($section['markup']) ? $section['markup'] : '';
			$comment = !empty($section['comment'])
				? $section['comment']
				: 'Two ways: include the file, or write the markup.';

			if ($markup !== '') {
				$code = '{# ' . $comment . " #}\n" . $include . "\n\n" . $markup;
			} else {
				$code = $include;
			}

			$sections[$id]['snippet'] = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
			$sections[$id]['params'] = isset($section['params']) ? $section['params'] : [];

			unset($sections[$id]['include'], $sections[$id]['markup'], $sections[$id]['comment']);
		}

		return $sections;
	}

	private function sections()
	{
		return [
			'button' => [
				'id' => 'button',
				'group' => 'Primitives',
				'title' => 'Button',
				'file' => 'components/button.twig',
				'description' => 'Primary actions. Variants: primary, secondary, destructive, ghost. Sizes: small, medium, large. Icon-only uses data-type="icon".',
				'markup' => '<button type="button" class="button-primary" data-size="medium">Primary</button>',
			],
			'badge' => [
				'id' => 'badge',
				'group' => 'Primitives',
				'title' => 'Badge',
				'file' => 'components/badge.twig',
				'description' => 'Compact status label. Variants: default, primary, secondary, destructive, warning, success. Also used on product cards and buttons.',
				'markup' => '<span class="badge" data-variant="success">New</span>',
			],
			'typography' => [
				'id' => 'typography',
				'group' => 'Primitives',
				'title' => 'Typography',
				'file' => 'components/typography.twig',
				'description' => 'Page headings and text roles. Use class heading on h1–h6, plus text, text-muted, text-em, link.',
				'markup' => "<h1 class=\"heading\">Title</h1>\n<p class=\"text-muted\">Hint</p>",
			],
			'icons' => [
				'id' => 'icons',
				'group' => 'Primitives',
				'title' => 'Icons',
				'file' => 'components/icons.twig',
				'description' => 'Source shapes for the sprite. In templates prefer <use href="/assets/icons/sprite.svg#icon-name">, not this file.',
				'markup' => '<svg aria-hidden="true" viewBox="0 0 24 24"><use href="/assets/icons/sprite.svg#icon-cart"></use></svg>',
			],
			'divider' => [
				'id' => 'divider',
				'group' => 'Primitives',
				'title' => 'Divider',
				'file' => 'components/divider.twig',
				'description' => 'Horizontal rule for sheets, cards, and stacked lists.',
				'markup' => '<hr class="divider" />',
			],
			'skeleton' => [
				'id' => 'skeleton',
				'group' => 'Primitives',
				'title' => 'Skeleton',
				'file' => 'components/skeleton.twig',
				'description' => 'Loading placeholder. Set width, height, and radius with utility classes.',
				'markup' => '<div class="skeleton h-12 w-12 rounded-full"></div>',
			],
			'theme-button' => [
				'id' => 'theme-button',
				'group' => 'Primitives',
				'title' => 'Theme toggle',
				'file' => 'components/theme-button.twig',
				'description' => 'Switches the html.dark class and writes the theme cookie. ui.js exposes toggleTheme().',
				'markup' => '<button class="button-ghost" type="button" data-type="icon" onclick="toggleTheme()" aria-label="Toggle theme"></button>',
			],
			'input' => [
				'id' => 'input',
				'group' => 'Forms',
				'title' => 'Input',
				'file' => 'components/input.twig',
				'description' => 'Labeled field with description, error, disabled, and readonly states. Sizes: sm, md, lg via data-size.',
				'markup' => "<div class=\"input-field\">\n  <label class=\"input-label\" for=\"email\">Email</label>\n  <input class=\"input\" id=\"email\" type=\"email\" />\n</div>",
			],
			'form' => [
				'id' => 'form',
				'group' => 'Forms',
				'title' => 'Form',
				'file' => 'components/form.twig',
				'description' => 'Form item layout: label, control, description, error. Covers input groups, checkbox, radio, select, textarea.',
				'markup' => "<div class=\"form-item required\">\n  <label class=\"form-label\" for=\"name\">Name</label>\n  <input class=\"input\" id=\"name\" />\n</div>",
			],
			'dropdown' => [
				'id' => 'dropdown',
				'group' => 'Forms',
				'title' => 'Dropdown',
				'file' => 'components/dropdown.twig',
				'description' => 'Menu anchored to a button. data-type="hover" or "click". data-align="left|center|right". Logic lives in ui.js.',
				'markup' => "<div class=\"dropdown\" data-dropdown data-type=\"click\" data-align=\"left\">\n  <button class=\"button-secondary\" type=\"button\" data-dropdown-button>Menu</button>\n  <div class=\"dropdown-menu\" data-dropdown-menu>\n    <a href=\"#\" class=\"dropdown-item\">Item 1</a>\n  </div>\n</div>",
			],
			'rating' => [
				'id' => 'rating',
				'group' => 'Forms',
				'title' => 'Rating',
				'file' => 'components/rating.twig',
				'description' => 'Read-only stars from a 1–5 value, or an interactive radio group when interactive is true (reviews).',
				'include' => $this->includeOf('components/rating.twig', '{ rating: 4 }'),
				'params' => [
					$this->param('rating', 'number', false, 'Filled stars, 1–5. Ignored when interactive is true.', '0'),
					$this->param('interactive', 'bool', false, 'Renders a radio group instead of a static value.', 'false'),
					$this->param('name', 'string', false, 'Radio input name when interactive.', 'rating'),
					$this->param('id', 'string', false, 'Radio input id prefix when interactive.', 'rating'),
					$this->param('entry_rating', 'string', false, 'Accessible label for the radio group.'),
				],
			],
			'custom-fields' => [
				'id' => 'custom-fields',
				'group' => 'Forms',
				'title' => 'Custom fields',
				'file' => 'components/custom-fields.twig',
				'description' => 'Renders OpenCart custom fields for account and checkout. Pass custom_fields plus optional field_values and error_custom_field.',
				'include' => $this->includeOf('components/custom-fields.twig', '{ custom_fields: custom_fields }'),
				'params' => [
					$this->param('custom_fields', 'array', true, 'OpenCart custom field definitions.'),
					$this->param('location', 'string', false, 'If set, only fields with this location are rendered (account, address, ...).'),
					$this->param('field_values', 'object', false, 'Current values, keyed by custom_field_id or by location then id.', '{}'),
					$this->param('error_custom_field', 'object', false, 'Validation messages keyed by custom_field_id.', '{}'),
					$this->param('text_select', 'string', false, 'Placeholder for select fields.'),
				],
			],
			'modal' => [
				'id' => 'modal',
				'group' => 'Overlays',
				'title' => 'Modal',
				'file' => 'components/modal.twig',
				'description' => 'Centered dialog. Open with data-modal-open="[data-modal]", close with overlay, close button, or Escape.',
				'markup' => '<button type="button" class="button-primary" data-modal-open="[data-modal]">Open</button>',
			],
			'sheet' => [
				'id' => 'sheet',
				'group' => 'Overlays',
				'title' => 'Sheet',
				'file' => 'components/sheet.twig',
				'description' => 'Side drawer for mobile navigation. data-side="left|right". Open with data-sheet-open="name".',
				'markup' => '<button type="button" class="button-primary" data-sheet-open="left-sheet">Menu</button>',
			],
			'toast' => [
				'id' => 'toast',
				'group' => 'Overlays',
				'title' => 'Toast',
				'file' => 'components/toast.twig',
				'description' => 'Transient notice. Call sendToast({ title, description, type, align }). Align: right-top, right-bottom, left-top, left-bottom.',
				'comment' => 'Include the template once, then call sendToast().',
				'markup' => "sendToast({ title: 'Saved', type: 'success', align: 'right-top' });",
			],
			'breadcrumbs' => [
				'id' => 'breadcrumbs',
				'group' => 'Content',
				'title' => 'Breadcrumbs',
				'file' => 'components/breadcrumbs.twig',
				'description' => 'Path trail. Last item is the current page (no link). Each item needs text and href.',
				'include' => $this->includeOf('components/breadcrumbs.twig', '{ breadcrumbs: breadcrumbs }'),
				'params' => [
					$this->param('breadcrumbs', 'array', true, 'Trail items. Last item is rendered as the current page.'),
					$this->param('breadcrumbs[].text', 'string', true, 'Label.'),
					$this->param('breadcrumbs[].href', 'string', true, 'Link. Ignored on the last item.'),
				],
			],
			'text-block' => [
				'id' => 'text-block',
				'group' => 'Content',
				'title' => 'Text block',
				'file' => 'components/text-block.twig',
				'description' => 'HTML content from CMS fields (information, blog, product description). Headings, lists, and paragraphs pick up .text-block styles.',
				'markup' => '<div class="text-block">{{ description }}</div>',
			],
			'accordion' => [
				'id' => 'accordion',
				'group' => 'Content',
				'title' => 'Accordion',
				'file' => 'components/accordion.twig',
				'description' => 'Native details/summary. Several items can stay open. Used by FAQ. Pass items: [{ title, content, open }].',
				'include' => $this->includeOf('components/accordion.twig', '{ items: items }'),
				'params' => [
					$this->param('items', 'array', true, 'Accordion rows. Hidden entirely when empty.'),
					$this->param('items[].title', 'string', true, 'Summary label.'),
					$this->param('items[].content', 'html', true, 'Panel body. Rendered as HTML.'),
					$this->param('items[].open', 'bool', false, 'When true, the item starts expanded.', 'false'),
				],
			],
			'pagination' => [
				'id' => 'pagination',
				'group' => 'Content',
				'title' => 'Pagination',
				'file' => 'components/pagination.twig',
				'description' => 'Listing pager. url must contain {page}. Shown only when there is more than one page.',
				'include' => $this->includeOf('components/pagination.twig', '{ pagination: pagination }'),
				'params' => [
					$this->param('pagination', 'object', true, 'Pager data. Markup is omitted when there is only one page.'),
					$this->param('pagination.total', 'number', true, 'Total number of items.'),
					$this->param('pagination.page', 'number', false, 'Current page.', '1'),
					$this->param('pagination.limit', 'number', false, 'Items per page.', '10'),
					$this->param('pagination.url', 'string', true, 'Page URL template. Must contain {page}.'),
					$this->param('pagination.text_next', 'string', true, 'Next button label.'),
					$this->param('pagination.text_prev', 'string', true, 'Previous button label.'),
					$this->param('pagination.num_links', 'number', false, 'How many numbered links to show around the current page.', '5'),
				],
			],
			'carousel' => [
				'id' => 'carousel',
				'group' => 'Content',
				'title' => 'Carousel',
				'file' => 'components/carousel.twig',
				'description' => 'Embla slideshow. Config: id, slides, slides_mobile, use_picture, controls, dots, autoplay. Banner slides need image, image_mobile, title, link.',
				'include' => $this->includeOf('components/carousel.twig', '{ slides: slides, config: config }'),
				'params' => [
					$this->param('slides', 'array', true, 'Slides. Each item: image, title, optional link and image_mobile.'),
					$this->param('config', 'object', false, 'Merged with defaults. Keys can also be passed as top-level variables.', '{}'),
					$this->param('config.id', 'string', false, 'Unique carousel id.', 'carousel'),
					$this->param('config.slides', 'number', false, 'Visible slides on desktop.', '1'),
					$this->param('config.slides_mobile', 'number', false, 'Visible slides on mobile.', '1'),
					$this->param('config.use_picture', 'bool', false, 'Use <picture> with image_mobile.', 'false'),
					$this->param('config.use_controls', 'bool', false, 'Prev/next on desktop.', 'true'),
					$this->param('config.use_dots', 'bool', false, 'Dots on desktop.', 'true'),
					$this->param('config.use_autoplay', 'bool', false, 'Autoplay on desktop.', 'true'),
					$this->param('config.use_loop', 'bool', false, 'Loop on desktop.', 'true'),
					$this->param('config.breakpoint', 'number', false, 'Mobile/desktop breakpoint in px.', '768'),
				],
			],
			'product-card' => [
				'id' => 'product-card',
				'group' => 'Catalog',
				'title' => 'Product card',
				'file' => 'components/product-card.twig',
				'description' => 'Listing card from product/helper::prepareProduct(). Optional config flags: badges, favorite, description.',
				'include' => $this->includeOf('components/product-card.twig', '{ product: product }'),
				'params' => [
					$this->param('product', 'object', true, 'From product/helper::prepareProduct(). Uses product_id, href, thumb, name, description, price, special, quantity, badges.'),
					$this->param('config', 'object', false, 'Flags and class overrides. Top-level aliases (badges, favorite, class, ...) are merged in.', '{}'),
					$this->param('config.badges', 'bool', false, 'Show product.badges on the image.', 'true'),
					$this->param('config.favorite', 'bool', false, 'Show the wishlist button. Hidden when product.remove is set.', 'true'),
					$this->param('config.description', 'bool', false, 'Reserved flag. Description still follows product.description.', 'true'),
					$this->param('product.remove', 'string', false, 'If set, the heart is replaced by a remove link (wishlist page).'),
				],
			],
			'article-card' => [
				'id' => 'article-card',
				'group' => 'Catalog',
				'title' => 'Article card',
				'file' => 'components/article-card.twig',
				'description' => 'Blog listing card. Needs article from blog/helper::prepareArticle() and button_more.',
				'include' => $this->includeOf('components/article-card.twig', '{ article: article, button_more: button_more }'),
				'params' => [
					$this->param('article', 'object', true, 'From blog/helper::prepareArticle(). Uses href, thumb, name, description, date_added.'),
					$this->param('button_more', 'string', true, 'Label for the card footer link.'),
				],
			],
			'article-slider' => [
				'id' => 'article-slider',
				'group' => 'Catalog',
				'title' => 'Article slider',
				'file' => 'components/article-slider.twig',
				'description' => 'Blog module row: heading plus grid or Embla slider. slider_config.id must match heading_id.',
				'include' => $this->includeOf('components/article-slider.twig', '{ articles: articles, heading_id: heading_id, heading_title: heading_title, slider: true, button_more: button_more, slider_config: slider_config }'),
				'params' => [
					$this->param('articles', 'array', true, 'Articles passed through to article-card. Hidden when empty.'),
					$this->param('heading_id', 'string', true, 'Heading id and slider data attribute. Must match slider_config.id.'),
					$this->param('heading_title', 'string', true, 'Section heading.'),
					$this->param('button_more', 'string', true, 'Forwarded to each article-card.'),
					$this->param('slider', 'bool', false, 'When true, renders Embla. When false, a static grid.', 'false'),
					$this->param('slider_config', 'object', false, 'Embla options when slider is true.', '{}'),
					$this->param('slider_config.id', 'string', true, 'Must equal heading_id.'),
					$this->param('slider_config.use_controls', 'bool', false, 'Prev/next on desktop.'),
					$this->param('slider_config.use_controls_mobile', 'bool', false, 'Prev/next on mobile.'),
					$this->param('slider_config.use_loop', 'bool', false, 'Loop on desktop.'),
					$this->param('slider_config.gap', 'number', false, 'Slide gap on desktop, px.'),
					$this->param('slider_config.gap_mobile', 'number', false, 'Slide gap on mobile, px.'),
					$this->param('slider_config.breakpoint', 'number', false, 'Mobile/desktop breakpoint in px.', '1024'),
				],
			],
			'product-gallery' => [
				'id' => 'product-gallery',
				'group' => 'Catalog',
				'title' => 'Product gallery',
				'file' => 'components/product-gallery.twig',
				'description' => 'Main image plus thumbs. Each item: preview (large) and thumb. Clicking a thumb swaps #product-image.',
				'include' => $this->includeOf('components/product-gallery.twig', '{ gallery: gallery, heading_title: heading_title }'),
				'params' => [
					$this->param('gallery', 'array', true, 'Images. Hidden when empty. First item is the main image.'),
					$this->param('gallery[].preview', 'string', true, 'Large image URL. Written to #product-image on click.'),
					$this->param('gallery[].thumb', 'string', true, 'Thumbnail URL.'),
					$this->param('heading_title', 'string', false, 'Alt text for images.'),
				],
			],
			'product-options' => [
				'id' => 'product-options',
				'group' => 'Catalog',
				'title' => 'Product options',
				'file' => 'components/product-options.twig',
				'description' => 'Buy-box options: select, radio, checkbox, text, textarea, file, date. Used on the product page.',
				'include' => $this->includeOf('components/product-options.twig', '{ options: options, text_option: text_option, text_select: text_select, button_upload: button_upload }'),
				'params' => [
					$this->param('options', 'array', true, 'Product options from OpenCart. Hidden when empty.'),
					$this->param('options[].product_option_id', 'number', true, 'Option id, used in input names.'),
					$this->param('options[].name', 'string', true, 'Option label.'),
					$this->param('options[].type', 'string', true, 'select, radio, checkbox, text, textarea, file, date, time, datetime.'),
					$this->param('options[].required', 'bool', false, 'Adds the required class on the field.', 'false'),
					$this->param('options[].product_option_value', 'array', false, 'Values for select, radio, and checkbox.'),
					$this->param('text_option', 'string', true, 'Heading above the option list.'),
					$this->param('text_select', 'string', true, 'Placeholder for select options.'),
					$this->param('button_upload', 'string', false, 'Label for file options.'),
				],
			],
			'product-recurrings' => [
				'id' => 'product-recurrings',
				'group' => 'Catalog',
				'title' => 'Product recurrings',
				'file' => 'components/product-recurrings.twig',
				'description' => 'Subscription profile select on the product page. Hidden when the product has no recurring plans.',
				'include' => $this->includeOf('components/product-recurrings.twig', '{ recurrings: recurrings, text_payment_recurring: text_payment_recurring, text_select: text_select }'),
				'params' => [
					$this->param('recurrings', 'array', true, 'Profiles. Hidden when empty.'),
					$this->param('recurrings[].recurring_id', 'number', true, 'Value of the select option.'),
					$this->param('recurrings[].name', 'string', true, 'Label of the select option.'),
					$this->param('text_payment_recurring', 'string', true, 'Field label.'),
					$this->param('text_select', 'string', true, 'Empty-option placeholder.'),
				],
			],
			'faq' => [
				'id' => 'faq',
				'group' => 'Catalog',
				'title' => 'FAQ',
				'file' => 'components/faq.twig',
				'description' => 'Storefront FAQ block. Wraps accordion. Schema is JSON-LD in common/microdata, not on this markup. See Docs → FAQ.',
				'include' => $this->includeOf('components/faq.twig', '{ faq: faq }'),
				'params' => [
					$this->param('faq', 'object', true, 'FAQ block. Hidden when faq.items is empty.'),
					$this->param('faq.title', 'string', false, 'Section heading.'),
					$this->param('faq.items', 'array', true, 'Passed to accordion as items: [{ title, content, open }].'),
				],
			],
		];
	}

	private function getProducts()
	{
		$this->load->model('catalog/product');
		$this->load->model('product/helper');

		$products = [];
		$results = $this->model_catalog_product->getProducts([
			'sort' => 'p.sort_order',
			'order' => 'ASC',
			'start' => 0,
			'limit' => 4,
		]);

		foreach ($results as $result) {
			$products[] = $this->model_product_helper->prepareProduct(
				$result,
				$this->url->link('product/product', 'product_id=' . $result['product_id'])
			);
		}

		return $products;
	}

	private function getArticles()
	{
		if (!is_file(DIR_APPLICATION . 'model/blog/article.php')) {
			return [];
		}

		$this->load->language('blog/latest');
		$this->load->model('blog/article');
		$this->load->model('blog/helper');

		$articles = [];
		$results = $this->model_blog_article->getArticles([
			'sort' => 'p.date_added',
			'order' => 'DESC',
			'start' => 0,
			'limit' => 4,
		]);

		foreach ($results as $result) {
			$articles[] = $this->model_blog_helper->prepareArticle($result);
		}

		return $articles;
	}

	private function getCategories()
	{
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');

		$categories = [];

		foreach ($this->model_catalog_category->getCategories(0) as $category) {
			if (empty($category['top'])) {
				continue;
			}

			$children_data = [];

			foreach ($this->model_catalog_category->getCategories($category['category_id']) as $child) {
				$children_data[] = [
					'name' => $child['name'],
					'href' => $this->url->link(
						'product/category',
						'path=' . $category['category_id'] . '_' . $child['category_id']
					),
				];
			}

			$categories[] = [
				'name' => $category['name'],
				'children' => $children_data,
				'href' => $this->url->link('product/category', 'path=' . $category['category_id']),
			];
		}

		if ($categories) {
			return $categories;
		}

		return [
			['name' => 'Catalog', 'children' => [], 'href' => $this->url->link('common/home')],
			['name' => 'Blog', 'children' => [], 'href' => $this->url->link('common/home')],
		];
	}

	private function getBreadcrumbs()
	{
		return [
			[
				'text' => 'Home',
				'href' => $this->url->link('common/home'),
			],
			[
				'text' => 'Components',
				'href' => $this->url->link('docs/components'),
			],
			[
				'text' => 'Showcase',
				'href' => $this->url->link('docs/components'),
			],
		];
	}

	private function getCarouselSlides()
	{
		$this->load->model('design/banner');
		$this->load->model('tool/image');

		$slides = [];
		$results = $this->model_design_banner->getBanner(8);

		foreach ($results as $result) {
			if (!is_file(DIR_IMAGE . $result['image'])) {
				continue;
			}

			$mobile_image =
				!empty($result['mobile_image']) && is_file(DIR_IMAGE . $result['mobile_image'])
					? $result['mobile_image']
					: $result['image'];

			$slides[] = [
				'title' => $result['title'],
				'image_mobile' => $this->model_tool_image->resize($mobile_image, 375, 210),
				'image' => $this->model_tool_image->resize($result['image'], 1920, 1080),
				'link' => $result['link'],
			];
		}

		return $slides;
	}

	private function getGallery(array $products)
	{
		$gallery = [];

		foreach ($products as $product) {
			if (empty($product['thumb'])) {
				continue;
			}

			$gallery[] = [
				'preview' => $product['thumb'],
				'thumb' => $product['thumb'],
			];
		}

		return $gallery;
	}

	private function sampleOptions()
	{
		return [
			[
				'product_option_id' => 1,
				'name' => 'Size',
				'type' => 'select',
				'required' => true,
				'product_option_value' => [
					['product_option_value_id' => 11, 'name' => 'S', 'price' => '', 'price_prefix' => '', 'image' => ''],
					['product_option_value_id' => 12, 'name' => 'M', 'price' => '', 'price_prefix' => '', 'image' => ''],
					['product_option_value_id' => 13, 'name' => 'L', 'price' => '+20', 'price_prefix' => '+', 'image' => ''],
				],
			],
			[
				'product_option_id' => 2,
				'name' => 'Color',
				'type' => 'radio',
				'required' => false,
				'product_option_value' => [
					['product_option_value_id' => 21, 'name' => 'Black', 'price' => '', 'price_prefix' => '', 'image' => ''],
					['product_option_value_id' => 22, 'name' => 'White', 'price' => '', 'price_prefix' => '', 'image' => ''],
				],
			],
			[
				'product_option_id' => 3,
				'name' => 'Engraving',
				'type' => 'text',
				'required' => false,
				'value' => '',
			],
		];
	}

	private function sampleRecurrings()
	{
		return [
			['recurring_id' => 1, 'name' => 'Monthly'],
			['recurring_id' => 2, 'name' => 'Yearly'],
		];
	}

	private function sampleCustomFields()
	{
		return [
			[
				'custom_field_id' => 1,
				'name' => 'Company',
				'type' => 'text',
				'location' => 'account',
				'sort_order' => 1,
				'value' => '',
				'custom_field_value' => [],
			],
			[
				'custom_field_id' => 2,
				'name' => 'Preferred contact',
				'type' => 'select',
				'location' => 'account',
				'sort_order' => 2,
				'value' => '',
				'custom_field_value' => [
					['custom_field_value_id' => 1, 'name' => 'Phone'],
					['custom_field_value_id' => 2, 'name' => 'Email'],
				],
			],
		];
	}

	private function sampleFaq()
	{
		return [
			'title' => 'Questions',
			'items' => [
				[
					'title' => 'Where do questions come from?',
					'content' => 'Admin → Extensions → Modules → FAQ, plus the FAQ tab on product, category, and manufacturer forms.',
				],
				[
					'title' => 'Is schema on this accordion?',
					'content' => 'No. FAQPage JSON-LD is emitted by common/microdata only, so Google does not see a duplicate FAQPage.',
				],
			],
		];
	}
}

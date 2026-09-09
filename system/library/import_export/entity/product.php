<?php

namespace import_export\entity;

class Product extends Base
{
	public function code()
	{
		return 'product';
	}

	public function schema()
	{
		return [
			['key' => 'product_id', 'type' => 'int'],
			['key' => 'sku'],
			['key' => 'model'],
			['key' => 'price', 'type' => 'float'],
			['key' => 'quantity', 'type' => 'int'],
			['key' => 'status', 'type' => 'bool'],
			['key' => 'noindex', 'type' => 'bool'],
			['key' => 'subtract', 'type' => 'bool'],
			['key' => 'shipping', 'type' => 'bool'],
			['key' => 'minimum', 'type' => 'int'],
			['key' => 'sort_order', 'type' => 'int'],
			['key' => 'weight', 'type' => 'float'],
			['key' => 'length', 'type' => 'float'],
			['key' => 'width', 'type' => 'float'],
			['key' => 'height', 'type' => 'float'],
			['key' => 'tax_class_id', 'type' => 'int'],
			['key' => 'manufacturer_id', 'type' => 'int'],
			['key' => 'manufacturer'],
			['key' => 'category_ids', 'list' => true],
			['key' => 'categories', 'list' => true],
			['key' => 'image'],
			['key' => 'images', 'list' => true],
			['key' => 'name', 'localized' => true],
			['key' => 'description', 'localized' => true],
			['key' => 'tag', 'localized' => true],
			['key' => 'meta_title', 'localized' => true],
			['key' => 'meta_h1', 'localized' => true],
			['key' => 'meta_description', 'localized' => true],
			['key' => 'meta_keyword', 'localized' => true],
			['key' => 'keyword', 'localized' => true],
			['key' => 'attributes', 'json' => true],
		];
	}

	public function example()
	{
		return [
			'product_id' => 0,
			'sku' => 'TEE-001',
			'model' => 'TEE-001',
			'price' => 499,
			'quantity' => 10,
			'status' => 1,
			'noindex' => 0,
			'manufacturer_id' => 1,
			'manufacturer' => 'Acme',
			'category_ids' => [12],
			'categories' => ['Clothes > T-shirts'],
			'image' => 'catalog/demo/tee-001.jpg',
			'images' => ['catalog/demo/tee-001-2.jpg'],
			'name' => ['uk' => 'Футболка', 'en' => 'T-shirt'],
			'description' => ['uk' => '', 'en' => ''],
			'keyword' => ['uk' => 'futbolka-tee-001', 'en' => 't-shirt-tee-001'],
			'attributes' => [
				['attribute_id' => 7, 'text' => ['uk' => 'Чорний', 'en' => 'Black']],
			],
		];
	}

	public function identity(array $row)
	{
		$id = $this->rowId($row, 'product_id');
		$key = $this->ctx->productKey();
		$value = trim((string) $this->mapper->scalar($row, $key));

		if ($value === '' && $key === 'sku') {
			$value = trim((string) $this->mapper->scalar($row, 'model'));
		} elseif ($value === '') {
			$value = trim((string) $this->mapper->scalar($row, 'sku'));
		}

		if ($id) {
			return $value !== '' ? '#' . $id . ' ' . $value : '#' . $id;
		}

		return $value;
	}

	public function find(array $row)
	{
		$stated = $this->lookupId($row, 'product_id', 'product', 'product_id');

		if ($stated > 0) {
			return $stated;
		}

		if ($stated < 0) {
			return 0;
		}

		if ($this->isForceCreate($row, 'product_id')) {
			return 0;
		}

		$key = $this->ctx->productKey();
		$value = trim((string) $this->mapper->scalar($row, $key));

		if ($value !== '') {
			$id = $this->resolver->productId($value, $key);

			if ($id) {
				return $id;
			}
		}

		$other = $key === 'sku' ? 'model' : 'sku';
		$fallback = trim((string) $this->mapper->scalar($row, $other));

		return $fallback !== '' ? $this->resolver->productId($fallback, $other) : 0;
	}

	public function read($id)
	{
		$model = $this->ctx->model('catalog/product');
		$row = $model->getProduct($id);

		if (!$row) {
			return [];
		}

		$descriptions = $model->getProductDescriptions($id);
		$seo = $model->getProductSeoUrls($id);
		$keyword = [];

		if (isset($seo[0]) && is_array($seo[0])) {
			$keyword = $this->mapper->idsToCodes($seo[0]);
		}

		$manufacturer = '';

		if (!empty($row['manufacturer_id'])) {
			$m = $this->ctx->model('catalog/manufacturer')->getManufacturer($row['manufacturer_id']);
			$manufacturer = $m ? $m['name'] : '';
		}

		$category_ids = $model->getProductCategories($id);
		$categories = [];

		foreach ($category_ids as $category_id) {
			$path = $this->resolver->categoryPath($category_id);

			if ($path !== '') {
				$categories[] = $path;
			}
		}

		$images = [];

		foreach ($model->getProductImages($id) as $image) {
			$images[] = $image['image'];
		}

		return [
			'product_id' => (int) $id,
			'sku' => $row['sku'],
			'model' => $row['model'],
			'price' => (float) $row['price'],
			'quantity' => (int) $row['quantity'],
			'status' => (int) $row['status'],
			'noindex' => (int) $row['noindex'],
			'subtract' => (int) $row['subtract'],
			'shipping' => (int) $row['shipping'],
			'minimum' => (int) $row['minimum'],
			'sort_order' => (int) $row['sort_order'],
			'weight' => (float) $row['weight'],
			'length' => (float) $row['length'],
			'width' => (float) $row['width'],
			'height' => (float) $row['height'],
			'tax_class_id' => (int) $row['tax_class_id'],
			'manufacturer_id' => (int) $row['manufacturer_id'],
			'manufacturer' => $manufacturer,
			'category_ids' => array_map('intval', $category_ids),
			'categories' => $categories,
			'image' => $row['image'],
			'images' => $images,
			'name' => $this->mapper->idsToCodes($this->pluck($descriptions, 'name')),
			'description' => $this->mapper->idsToCodes($this->pluck($descriptions, 'description')),
			'tag' => $this->mapper->idsToCodes($this->pluck($descriptions, 'tag')),
			'meta_title' => $this->mapper->idsToCodes($this->pluck($descriptions, 'meta_title')),
			'meta_h1' => $this->mapper->idsToCodes($this->pluck($descriptions, 'meta_h1')),
			'meta_description' => $this->mapper->idsToCodes($this->pluck($descriptions, 'meta_description')),
			'meta_keyword' => $this->mapper->idsToCodes($this->pluck($descriptions, 'meta_keyword')),
			'keyword' => $keyword,
			'attributes' => $this->readAttributes($id),
		];
	}

	public function exportAll(array $filter = [])
	{
		$model = $this->ctx->model('catalog/product');
		$query = [];

		if (!empty($filter['category_id'])) {
			$query['filter_category'] = (int) $filter['category_id'];
		}

		if (!empty($filter['manufacturer_id'])) {
			$query['filter_manufacturer_id'] = (int) $filter['manufacturer_id'];
		}

		if (isset($filter['status']) && $filter['status'] !== '') {
			$query['filter_status'] = (int) $filter['status'];
		}

		$rows = [];

		foreach ($model->getProducts($query) as $item) {
			$rows[] = $this->read($item['product_id']);
		}

		return $rows;
	}

	public function preview(array $row)
	{
		$stated = $this->lookupId($row, 'product_id', 'product', 'product_id');

		if ($stated < 0) {
			return $this->unknownIdError('product_id', $this->rowId($row, 'product_id'));
		}

		$identity = $this->identity($row);

		if ($stated < 1 && $identity === '') {
			return $this->result('error', '', '', 'product_id or sku is required.');
		}

		$error = $this->refsError($row);

		if ($error) {
			$action = $this->missingMode() === 'skip' ? 'skip' : 'error';

			return $this->result($action, $identity, $this->localizedName($row) ?: $identity, $error);
		}

		$id = $this->find($row);

		return $this->result($id ? 'update' : 'create', $identity, $this->localizedName($row) ?: $identity, '', $id);
	}

	public function write(array $row)
	{
		$preview = $this->preview($row);

		if ($preview['action'] === 'error' || $preview['action'] === 'skip') {
			$preview['action'] = $preview['action'] === 'skip' ? 'skipped' : 'error';

			return $preview;
		}

		$identity = $this->identity($row);
		$model = $this->ctx->model('catalog/product');
		$id = $this->find($row);
		$data = $id ? $this->form($id) : $this->defaults($row);

		foreach (['sku', 'model', 'upc', 'ean', 'jan', 'isbn', 'mpn', 'location'] as $field) {
			if ($this->mapper->has($row, $field)) {
				$data[$field] = (string) $row[$field];
			}
		}

		if (!$this->mapper->has($row, 'model') && !empty($data['sku']) && $data['model'] === '') {
			$data['model'] = $data['sku'];
		}

		foreach (['price', 'weight', 'length', 'width', 'height'] as $field) {
			if ($this->mapper->has($row, $field)) {
				$data[$field] = (float) $row[$field];
			}
		}

		foreach (['quantity', 'status', 'noindex', 'subtract', 'shipping', 'minimum', 'sort_order', 'tax_class_id', 'points'] as $field) {
			if ($this->mapper->has($row, $field)) {
				$data[$field] = (int) $row[$field];
			}
		}

		if ($this->mapper->has($row, 'manufacturer_id')) {
			$data['manufacturer_id'] = $this->resolveFk('manufacturer', $this->rowId($row, 'manufacturer_id'));
		} elseif ($this->mapper->has($row, 'manufacturer')) {
			$data['manufacturer_id'] = $this->resolver->manufacturerId($row['manufacturer'], $this->createRefs());
		}

		if ($this->mapper->has($row, 'image')) {
			$image = $this->mapper->imagePath($row['image']);

			if ($image !== false) {
				$data['image'] = $image;
			}
		}

		$images = $this->mapper->listValues($row, 'images');

		if ($images !== null) {
			$data['product_image'] = [];

			foreach ($images as $i => $path) {
				$image = $this->mapper->imagePath($path);

				if ($image === false || $image === '') {
					continue;
				}

				$data['product_image'][] = ['image' => $image, 'sort_order' => $i];
			}
		}

		$category_ids = $this->resolveCategoryIds($row);

		if ($category_ids !== null) {
			$data['product_category'] = $category_ids;
			$data['main_category_id'] = $category_ids ? $category_ids[0] : 0;
		}

		$attributes = $this->mapper->jsonField($row, 'attributes');

		if ($attributes !== null) {
			$data['product_attribute'] = $this->writeAttributes($attributes);
		}

		$data['product_description'] = $this->mapper->mergeDescription(
			$data['product_description'],
			$row,
			['name', 'description', 'tag', 'meta_title', 'meta_h1', 'meta_description', 'meta_keyword']
		);

		$seo = $this->mapper->seoMap(isset($row['keyword']) ? $row['keyword'] : null);

		if ($seo !== null) {
			$data['product_seo_url'] = $seo;
		}

		if ($id) {
			$model->editProduct($id, $data);
			$this->resolver->rememberProduct($this->ctx->productKey(), $identity, $id);

			return $this->finishWrite($row, 'product_id', 'product', $this->result('updated', $identity, $this->localizedName($row) ?: $identity, '', $id));
		}

		$id = (int) $model->addProduct($data);
		$this->resolver->rememberProduct($this->ctx->productKey(), $identity, $id);

		return $this->finishWrite($row, 'product_id', 'product', $this->result('created', $identity, $this->localizedName($row) ?: $identity, '', $id));
	}

	private function refsError(array $row)
	{
		$manufacturer_id = $this->rowId($row, 'manufacturer_id');

		if ($manufacturer_id) {
			$error = $this->unknownRef('manufacturer_id', $manufacturer_id);

			if ($error) {
				return $error;
			}
		} elseif (!$this->createRefs() && $this->mapper->has($row, 'manufacturer')) {
			if (!$this->resolver->manufacturerId($row['manufacturer'], false)) {
				return 'Unknown manufacturer: ' . $row['manufacturer'];
			}
		}

		$ids = $this->mapper->listValues($row, 'category_ids');

		if ($ids) {
			foreach ($ids as $category_id) {
				$error = $this->unknownRef('category_id', (int) $category_id);

				if ($error) {
					return $error;
				}
			}
		} elseif (!$this->createRefs()) {
			$categories = $this->mapper->listValues($row, 'categories');

			if ($categories) {
				foreach ($categories as $item) {
					if (is_numeric($item)) {
						$error = $this->unknownRef('category_id', (int) $item);

						if ($error) {
							return $error;
						}
					} elseif (!$this->resolver->categoryIdByPath($item, false)) {
						return 'Unknown category: ' . $item;
					}
				}
			}
		}

		$attributes = $this->mapper->jsonField($row, 'attributes');

		if ($attributes) {
			foreach ($attributes as $attribute) {
				$attribute_id = isset($attribute['attribute_id']) ? (int) $attribute['attribute_id'] : 0;

				if ($attribute_id) {
					$error = $this->unknownRef('attribute_id', $attribute_id);

					if ($error) {
						return $error;
					}

					continue;
				}

				if ($this->createRefs()) {
					continue;
				}

				$name = isset($attribute['name']) ? $attribute['name'] : '';
				$group = isset($attribute['group']) ? $attribute['group'] : '';

				if (is_array($name)) {
					$name = $this->localizedName(['name' => $name]);
				}

				if ($name && !$this->resolver->attributeId($name, $group, false)) {
					return 'Unknown attribute: ' . $name;
				}
			}
		}

		return '';
	}

	private function resolveCategoryIds(array $row)
	{
		$ids = $this->mapper->listValues($row, 'category_ids');

		if ($ids !== null) {
			$out = [];

			foreach ($ids as $category_id) {
				$category_id = $this->resolveFk('category', (int) $category_id);

				if ($category_id) {
					$out[] = $category_id;
				}
			}

			return $out;
		}

		$categories = $this->mapper->listValues($row, 'categories');

		if ($categories === null) {
			return null;
		}

		$out = [];

		foreach ($categories as $item) {
			if (is_numeric($item)) {
				$category_id = $this->resolveFk('category', (int) $item);
			} else {
				$category_id = $this->resolver->categoryIdByPath($item, $this->createRefs());
			}

			if ($category_id) {
				$out[] = $category_id;
			}
		}

		return $out;
	}

	private function defaults(array $row)
	{
		$sku = (string) $this->mapper->scalar($row, 'sku');
		$model = (string) $this->mapper->scalar($row, 'model', $sku);

		return [
			'model' => $model !== '' ? $model : $sku,
			'sku' => $sku,
			'upc' => '',
			'ean' => '',
			'jan' => '',
			'isbn' => '',
			'mpn' => '',
			'location' => '',
			'quantity' => 0,
			'minimum' => 1,
			'subtract' => 1,
			'stock_status_id' => $this->ctx->stockStatusId(),
			'date_available' => date('Y-m-d'),
			'manufacturer_id' => 0,
			'shipping' => 1,
			'price' => 0,
			'points' => 0,
			'weight' => 0,
			'weight_class_id' => $this->ctx->weightClassId(),
			'length' => 0,
			'width' => 0,
			'height' => 0,
			'length_class_id' => $this->ctx->lengthClassId(),
			'status' => 1,
			'noindex' => 0,
			'tax_class_id' => 0,
			'sort_order' => 0,
			'image' => '',
			'product_description' => $this->ctx->emptyDescription(),
			'product_store' => $this->ctx->storeIds(),
			'product_category' => [],
			'main_category_id' => 0,
			'product_image' => [],
			'product_attribute' => [],
		];
	}

	private function form($id)
	{
		$model = $this->ctx->model('catalog/product');
		$row = $model->getProduct($id);
		$data = $this->defaults($row);

		foreach ([
			'model', 'sku', 'upc', 'ean', 'jan', 'isbn', 'mpn', 'location', 'quantity', 'minimum',
			'subtract', 'stock_status_id', 'date_available', 'manufacturer_id', 'shipping', 'price',
			'points', 'weight', 'weight_class_id', 'length', 'width', 'height', 'length_class_id',
			'status', 'noindex', 'tax_class_id', 'sort_order', 'image',
		] as $field) {
			if (isset($row[$field])) {
				$data[$field] = $row[$field];
			}
		}

		$data['product_description'] = $model->getProductDescriptions($id);
		$data['product_store'] = $model->getProductStores($id);
		$data['product_category'] = $model->getProductCategories($id);
		$data['main_category_id'] = $model->getProductMainCategoryId($id);
		$data['product_image'] = $model->getProductImages($id);
		$data['product_attribute'] = $model->getProductAttributes($id);
		$data['product_option'] = $model->getProductOptions($id);
		$data['product_discount'] = $model->getProductDiscounts($id);
		$data['product_special'] = $model->getProductSpecials($id);
		$data['product_related'] = $model->getProductRelated($id);
		$data['product_related_article'] = $model->getArticleRelated($id);
		$data['product_reward'] = $model->getProductRewards($id);
		$data['product_download'] = $model->getProductDownloads($id);
		$data['product_layout'] = $model->getProductLayouts($id);
		$data['product_seo_url'] = $model->getProductSeoUrls($id);
		$data['product_filter'] = $model->getProductFilters($id);
		$data['product_recurring'] = $model->getRecurrings($id);

		return $data;
	}

	private function readAttributes($product_id)
	{
		$model = $this->ctx->model('catalog/product');
		$attr_model = $this->ctx->model('catalog/attribute');
		$out = [];

		foreach ($model->getProductAttributes($product_id) as $item) {
			$info = $attr_model->getAttribute($item['attribute_id']);
			$names = $attr_model->getAttributeDescriptions($item['attribute_id']);
			$group = '';

			if ($info && !empty($info['attribute_group_id'])) {
				$group_names = $this->ctx->model('catalog/attribute_group')->getAttributeGroupDescriptions($info['attribute_group_id']);
				$default = $this->ctx->defaultLanguageId();
				$group = isset($group_names[$default]['name']) ? $group_names[$default]['name'] : '';
			}
			$name = $this->mapper->idsToCodes($this->pluck($names, 'name'));
			$text = [];

			if (!empty($item['product_attribute_description'])) {
				foreach ($item['product_attribute_description'] as $language_id => $row) {
					$text[$language_id] = $row['text'];
				}
			}

			$out[] = [
				'attribute_id' => (int) $item['attribute_id'],
				'attribute_group_id' => $info && !empty($info['attribute_group_id']) ? (int) $info['attribute_group_id'] : 0,
				'group' => $group,
				'name' => $name,
				'text' => $this->mapper->idsToCodes($text),
			];
		}

		return $out;
	}

	private function writeAttributes(array $attributes)
	{
		$out = [];

		foreach ($attributes as $attribute) {
			$id = isset($attribute['attribute_id']) ? (int) $attribute['attribute_id'] : 0;

			if ($id) {
				$id = $this->resolveFk('attribute', $id);

				if (!$id) {
					continue;
				}
			} else {
				$name = isset($attribute['name']) ? $attribute['name'] : '';
				$group = isset($attribute['group']) ? $attribute['group'] : '';

				if (is_array($name)) {
					$name = $this->localizedName(['name' => $name]);
				}

				$id = $this->resolver->attributeId($name, $group, $this->createRefs());
			}

			if (!$id) {
				continue;
			}

			$text_map = $this->mapper->localized(['text' => isset($attribute['text']) ? $attribute['text'] : ''], 'text');
			$description = [];

			foreach ($this->ctx->languages() as $language) {
				$lid = $language['language_id'];
				$description[$lid] = ['text' => $text_map && isset($text_map[$lid]) ? $text_map[$lid] : ''];
			}

			$out[] = [
				'attribute_id' => $id,
				'product_attribute_description' => $description,
			];
		}

		return $out;
	}

	private function pluck(array $descriptions, $field)
	{
		$out = [];

		foreach ($descriptions as $language_id => $row) {
			$out[$language_id] = isset($row[$field]) ? $row[$field] : '';
		}

		return $out;
	}
}

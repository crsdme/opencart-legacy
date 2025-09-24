<?php
class ModelExtensionFeedSitemap extends Model
{
    public function getCategories(): array
    {
        $sql =
            "SELECT c.category_id, c.image, c.date_added, c.date_modified
                  FROM " .
            DB_PREFIX .
            "category c
             LEFT JOIN " .
            DB_PREFIX .
            "category_description cd
                    ON (c.category_id = cd.category_id)";

        if ($this->config->get('feed_branched_sitemap_multishop')) {
            $sql .= ' LEFT JOIN ' . DB_PREFIX . 'category_to_store c2s ON (c.category_id = c2s.category_id)';
        }

        $sql .=
            " WHERE cd.language_id = '" .
            (int) $this->config->get('config_language_id') .
            "'
                   AND c.status = '1'";

        static $catHasNoindex = null;
        if ($catHasNoindex === null) {
            $col = $this->db->query('SHOW COLUMNS FROM ' . DB_PREFIX . "category WHERE Field = 'noindex'");
            $catHasNoindex = (bool) $col->num_rows;
        }
        if ($catHasNoindex) {
            $sql .= " AND c.noindex = '1'";
        }

        if ($this->config->get('feed_branched_sitemap_multishop')) {
            $sql .= " AND c2s.store_id = '" . (int) $this->config->get('config_store_id') . "'";
        }

        $sql .= ' ORDER BY c.category_id ASC, LCASE(cd.name)';

        $q = $this->db->query($sql);
        return $q->rows;
    }

    public function getPathByCategory($category_id)
    {
        $category_id = (int) $category_id;
        if ($category_id < 1) {
            return false;
        }

        static $path = null;
        if (!isset($path)) {
            $path = $this->cache->get('category.seopath');
            if (!is_array($path)) {
                $path = [];
            }
        }

        if (!isset($path[$category_id])) {
            $max_level = 10;

            $sql = "SELECT CONCAT_WS('_'";
            for ($i = $max_level - 1; $i >= 0; --$i) {
                $sql .= ",t$i.category_id";
            }
            $sql .= ') AS path FROM ' . DB_PREFIX . 'category t0';
            for ($i = 1; $i < $max_level; ++$i) {
                $sql .= ' LEFT JOIN ' . DB_PREFIX . "category t$i ON (t$i.category_id = t" . ($i - 1) . '.parent_id)';
            }
            $sql .= " WHERE t0.category_id = '" . $category_id . "'";

            $query = $this->db->query($sql);

            $path[$category_id] = $query->num_rows ? $query->row['path'] : false;

            $this->cache->set('category.seopath', $path);
        }

        return $path[$category_id];
    }

    public function getProducts(): array
    {
        $sql =
            "SELECT p.product_id, p.image, p.date_added, p.date_modified, pd.name
                  FROM " .
            DB_PREFIX .
            "product p
             LEFT JOIN " .
            DB_PREFIX .
            "product_description pd
                    ON (p.product_id = pd.product_id)";

        if ($this->config->get('feed_branched_sitemap_multishop')) {
            $sql .= ' LEFT JOIN ' . DB_PREFIX . 'product_to_store p2s ON (p.product_id = p2s.product_id)';
        }

        $sql .=
            " WHERE p.status = '1'
                   AND p.date_available <= NOW()
                   AND pd.language_id = '" .
            (int) $this->config->get('config_language_id') .
            "'";

        // кэшируем проверку наличия поля noindex
        static $prodHasNoindex = null;
        if ($prodHasNoindex === null) {
            $col = $this->db->query('SHOW COLUMNS FROM ' . DB_PREFIX . "product WHERE Field = 'noindex'");
            $prodHasNoindex = (bool) $col->num_rows;
        }
        if ($prodHasNoindex) {
            $sql .= " AND p.noindex = '1'";
        }

        if ($this->config->get('feed_branched_sitemap_multishop')) {
            $sql .= " AND p2s.store_id = '" . (int) $this->config->get('config_store_id') . "'";
        }

        // на случай дублей из-за JOIN'ов
        $sql .= " GROUP BY p.product_id
                  ORDER BY p.product_id ASC";

        $q = $this->db->query($sql);
        return $q->rows;
    }

    public function getPathByProduct($product_id)
    {
        $product_id = (int) $product_id;
        if ($product_id < 1) {
            return false;
        }

        static $path = null;
        if (!isset($path)) {
            $path = $this->cache->get('product.seopath');
            if (!isset($path)) {
                $path = [];
            }
        }

        if (!isset($path[$product_id])) {
            $query = $this->db->query(
                'SELECT category_id FROM ' .
                    DB_PREFIX .
                    "product_to_category WHERE product_id = '" .
                    $product_id .
                    "' ORDER BY main_category DESC LIMIT 1",
            );

            $path[$product_id] = $this->getPathByCategory($query->num_rows ? (int) $query->row['category_id'] : 0);

            $this->cache->set('product.seopath', $path);
        }

        return $path[$product_id];
    }

    public function getInformation(): array
    {
        $sql =
            "SELECT i.information_id, id.title
              FROM " .
            DB_PREFIX .
            "information i
         LEFT JOIN " .
            DB_PREFIX .
            "information_description id
                ON (i.information_id = id.information_id)";

        if ($this->config->get('feed_branched_sitemap_multishop')) {
            $sql .= ' LEFT JOIN ' . DB_PREFIX . 'information_to_store i2s ON (i.information_id = i2s.information_id)';
        }

        $sql .=
            " WHERE id.language_id = '" .
            (int) $this->config->get('config_language_id') .
            "'
               AND i.status = '1'";

        static $infoHasNoindex = null;
        if ($infoHasNoindex === null) {
            $c = $this->db->query('SHOW COLUMNS FROM ' . DB_PREFIX . "information WHERE Field = 'noindex'");
            $infoHasNoindex = (bool) $c->num_rows;
        }
        if ($infoHasNoindex) {
            $sql .= " AND i.noindex = '1'";
        }

        if ($this->config->get('feed_branched_sitemap_multishop')) {
            $sql .= " AND i2s.store_id = '" . (int) $this->config->get('config_store_id') . "'";
        }

        $sql .= ' ORDER BY i.information_id ASC, LCASE(id.title)';

        $q = $this->db->query($sql);
        return $q->rows;
    }

    public function getManufacturers(): array
    {
        $sql =
            "SELECT m.manufacturer_id, m.name
              FROM " .
            DB_PREFIX .
            'manufacturer m';

        if ($this->config->get('feed_branched_sitemap_multishop')) {
            $sql .=
                ' LEFT JOIN ' . DB_PREFIX . 'manufacturer_to_store m2s ON (m.manufacturer_id = m2s.manufacturer_id)';
        }

        $sql .= " WHERE m.manufacturer_id != '0'";

        static $manHasNoindex = null;
        if ($manHasNoindex === null) {
            $c = $this->db->query('SHOW COLUMNS FROM ' . DB_PREFIX . "manufacturer WHERE Field = 'noindex'");
            $manHasNoindex = (bool) $c->num_rows;
        }
        if ($manHasNoindex) {
            $sql .= " AND m.noindex = '1'";
        }

        if ($this->config->get('feed_branched_sitemap_multishop')) {
            $sql .= " AND m2s.store_id = '" . (int) $this->config->get('config_store_id') . "'";
        }

        $sql .= ' ORDER BY m.manufacturer_id ASC, LCASE(m.name)';

        $q = $this->db->query($sql);
        return $q->rows;
    }

    public function getBlogCategories(): array
    {
        $sql =
            "SELECT c.blog_category_id, c.image, c.date_added, c.date_modified, cd.name
              FROM " .
            DB_PREFIX .
            "blog_category c
         LEFT JOIN " .
            DB_PREFIX .
            "blog_category_description cd
                ON (c.blog_category_id = cd.blog_category_id)";

        if ($this->config->get('feed_branched_sitemap_multishop')) {
            $sql .=
                ' LEFT JOIN ' .
                DB_PREFIX .
                'blog_category_to_store bc2s ON (c.blog_category_id = bc2s.blog_category_id)';
        }

        $sql .=
            " WHERE cd.language_id = '" .
            (int) $this->config->get('config_language_id') .
            "'
               AND c.status = '1'";

        if ($this->config->get('feed_branched_sitemap_multishop')) {
            $sql .= " AND bc2s.store_id = '" . (int) $this->config->get('config_store_id') . "'";
        }

        $sql .= ' ORDER BY c.blog_category_id ASC, LCASE(cd.name)';

        $q = $this->db->query($sql);
        return $q->rows;
    }

    public function getBlogArticles(): array
    {
        $sql =
            "SELECT a.article_id, a.date_added, a.date_modified, ad.name
              FROM " .
            DB_PREFIX .
            "article a
         LEFT JOIN " .
            DB_PREFIX .
            "article_description ad
                ON (a.article_id = ad.article_id)";

        if ($this->config->get('feed_branched_sitemap_multishop')) {
            $sql .= ' LEFT JOIN ' . DB_PREFIX . 'article_to_store a2s ON (a.article_id = a2s.article_id)';
        }

        $sql .=
            " WHERE ad.language_id = '" .
            (int) $this->config->get('config_language_id') .
            "'
               AND a.status = '1'";

        if ($this->config->get('feed_branched_sitemap_multishop')) {
            $sql .= " AND a2s.store_id = '" . (int) $this->config->get('config_store_id') . "'";
        }

        $sql .= ' ORDER BY a.article_id ASC, LCASE(ad.name)';

        $q = $this->db->query($sql);
        return $q->rows;
    }
}

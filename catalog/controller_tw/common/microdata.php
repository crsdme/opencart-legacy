<?php
class ControllerCommonMicrodata extends Controller
{
    public function index($data)
    {
        $microdata['head'] = $this->openGraphController($data);

        $jsonLdBlocks[] = $this->organizationController($data);

        $html = '';
        foreach ($jsonLdBlocks as $block) {
            $html .= '<!-- ' . $block['comment'] . ' -->' . PHP_EOL;
            $html .= '<script type="application/ld+json">' . PHP_EOL;
            $html .= $block['json'] . PHP_EOL;
            $html .= '</script>' . PHP_EOL;
            $html .= '<!-- ' . $block['comment'] . ' END -->' . PHP_EOL;
        }

        $microdata['body'] = str_replace('><', '>' . PHP_EOL . '<', $html . PHP_EOL);

        return $microdata;
    }

    private function openGraphController($data)
    {
        $data['locale'] = 'ru';
        $data['og_type'] = 'website';
        $data['title'] = 'test';
        $data['description'] = 'test';
        $data['image'] = 'test';
        $data['image_width'] = '100';
        $data['image_height'] = '100';
        $data['url'] = 'test';
        $data['street_address'] = 'test';
        $data['locality'] = 'test';
        $data['postal_code'] = 'test';
        $data['country_name'] = 'test';
        $data['latitude'] = 'test';
        $data['longitude'] = 'test';
        $data['email'] = 'test';
        $data['microdatapro_profile_id'] = 'test';

        return $this->openGraphSchema($data);
    }

    private function openGraphSchema($data)
    {
        $html = '';

        $html .= '<!--schema open graph start -->';
        $html .= '<meta property="og:locale" content="' . $data['locale'] . '">';
        $html .= '<meta property="og:type" content="' . $data['og_type'] . '">';
        $html .= '<meta property="og:title" content="' . $data['title'] . '">';
        $html .= '<meta property="og:description" content="' . $data['description'] . '">';
        $html .= '<meta property="og:image" content="' . $data['image'] . '">';
        $html .= '<meta property="og:image:width" content="' . $data['image_width'] . '">';
        $html .= '<meta property="og:image:height" content="' . $data['image_height'] . '">';
        $html .= '<meta property="og:url" content="' . $data['url'] . '">';
        $html .= '<meta property="business:contact_data:street_address" content="' . $data['street_address'] . '">';
        $html .= '<meta property="business:contact_data:locality" content="' . $data['locality'] . '">';
        $html .= '<meta property="business:contact_data:postal_code" content="' . $data['postal_code'] . '">';
        $html .= '<meta property="business:contact_data:country_name" content="' . $data['country_name'] . '">';
        $html .= '<meta property="place:location:latitude" content="' . $data['latitude'] . '">';
        $html .= '<meta property="place:location:longitude" content="' . $data['longitude'] . '">';
        $html .= '<meta property="business:contact_data:email" content="' . $data['email'] . '">';
        if ($data['microdatapro_profile_id']) {
            $html .= '<meta property="fb:profile_id" content="' . $data['microdatapro_profile_id'] . '">';
        }
        $html .= '<!--schema open graph end -->';

        return str_replace('><', '>' . PHP_EOL . '<', $html . PHP_EOL);
    }

    private function twitterController($data)
    {
        return $this->twitterSchema($data);
    }

    private function twitterSchema($data)
    {
        $html = '';

        if ($data['twitter']) {
            $html .= '<!--schema twitter cards start -->';
            $html .= '<meta property="twitter:card" content="summary_large_image">';
            $html .= '<meta property="twitter:creator" content="' . $data['twitter_account'] . '">';
            $html .= '<meta property="twitter:site" content="' . $data['twitter_account'] . '">';
            $html .= '<meta property="twitter:title" content="' . $data['title'] . '">';
            $html .= '<meta property="twitter:description" content="' . $data['description'] . '">';
            $html .= '<meta property="twitter:image" content="' . $data['image'] . '">';
            $html .= '<meta property="twitter:image:alt" content="' . $data['title'] . '">';
            $html .= '<!--schema twitter cards end -->';
        }

        return str_replace('><', '>' . PHP_EOL . '<', $html . PHP_EOL);
    }

    private function organizationController($data)
    {
        $data['organization_url'] = 'test';
        $data['organization_name'] = 'test';
        $data['organization_logo'] = 'test';
        $data['organization_email'] = 'test';
        $data['organization_groups'] = 'test';
        $data['organization_locations'] = [
            [
                'postalCode' => 'test',
                'streetAddress' => 'test',
                'latitude' => 'test',
                'longitude' => 'test',
            ],
        ];

        return $this->organizationSchema($data);
    }

    private function organizationSchema($data)
    {
        $organizationSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $data['organization_url'] . '#organization',
            'name' => $data['organization_name'],
            'url' => $data['organization_url'],
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $data['organization_logo'],
            ],
            'email' => $data['organization_email'],
            'sameAs' => $data['organization_groups'] ?? [],
            'address' => [
                '@type' => 'PostalAddress',
                'postalCode' => $data['organization_locations'][0]['postalCode'],
                'streetAddress' => $data['organization_locations'][0]['streetAddress'],
                'addressLocality' => 'Київ',
                'addressRegion' => 'Київ',
                'addressCountry' => 'UA',
            ],
            'contactPoint' => array_map(function ($contact) {
                return [
                    '@type' => 'ContactPoint',
                    'telephone' => '+380930002928',
                    'contactType' => 'customer service',
                    'areaServed' => 'UA',
                    'availableLanguage' => ['uk', 'ru'],
                ];
            }, $data['organization_phones'] ?? []),
        ];

        return [
            'comment' => 'Organization JSON-LD',
            'json' => json_encode(
                $organizationSchema,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
            ),
        ];
    }

    private function localBusinessController($data)
    {
        return $this->localBusinessSchema($data);
    }

    private function localBusinessSchema($data)
    {
        $localBusinessSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            '@id' => $data['organization_url'] . '#store',
            'name' => $data['organization_name'],
            'url' => $data['organization_url'],
            'image' => $data['organization_logo'],
            'logo' => $data['organization_logo'],
            'email' => $data['organization_email'],
            'hasMap' => $data['organization_map'] ?? null,
            'telephone' => '+380930002928',
            'sameAs' => $data['organization_groups'] ?? [],
            'currenciesAccepted' => 'UAH',
            'address' => isset($data['organization_locations'][0])
                ? (object) [
                    '@type' => 'PostalAddress',
                    'postalCode' => $data['organization_locations'][0]['postalCode'],
                    'streetAddress' => $data['organization_locations'][0]['streetAddress'],
                    'addressLocality' => 'Київ',
                    'addressRegion' => 'Київ',
                    'addressCountry' => 'UA',
                ]
                : null,
            'geo' => isset($data['organization_locations'][0])
                ? (object) [
                    '@type' => 'GeoCoordinates',
                    'latitude' => $data['organization_locations'][0]['latitude'],
                    'longitude' => $data['organization_locations'][0]['longitude'],
                ]
                : null,
            'openingHoursSpecification' => array_map(
                function ($day, $hours) {
                    return [
                        '@type' => 'OpeningHoursSpecification',
                        'dayOfWeek' => 'https://schema.org/' . $day,
                        'opens' => $hours['open'],
                        'closes' => $hours['close'],
                    ];
                },
                array_keys($data['organization_oh'] ?? []),
                $data['organization_oh'] ?? [],
            ),
            'priceRange' => '₴₴',
            'paymentAccepted' => 'Cash, Credit Card',
            'acceptedPaymentMethod' => ['Cash', 'CreditCard'],
        ];

        $jsonLdBlocks[] = [
            'comment' => 'LocalBusiness JSON-LD',
            'json' => json_encode(
                $localBusinessSchema,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
            ),
        ];
    }

    private function breadcrumbsController($data)
    {
        return $this->breadcrumbsSchema($data);
    }

    private function breadcrumbsSchema($data)
    {
        if (empty($data['breadcrumbs'])) {
            return;
        }

        $breadcrumbsSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                function ($breadcrumb, $key) {
                    return [
                        '@type' => 'ListItem',
                        'position' => $key,
                        'item' => [
                            '@id' => $breadcrumb['href'],
                            'name' => $breadcrumb['text'],
                        ],
                    ];
                },
                $data['breadcrumbs'],
                array_keys($data['breadcrumbs']),
            ),
        ];

        $jsonLdBlocks[] = [
            'comment' => 'Breadcrumbs JSON-LD',
            'json' => json_encode(
                $breadcrumbsSchema,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
            ),
        ];
    }

    private function productController($data)
    {
        return $this->productSchema($data);
    }

    private function productSchema($data)
    {
        // product
        $productSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'url' => $data['url'],
            'name' => $data['name'],
            'description' => $data['description'],
            'itemCondition' => 'https://schema.org/' . $data['condition'],
        ];

        // Дополнительные поля
        if (!empty($data['category'])) {
            $productSchema['category'] = $data['category'];
        }
        if (!empty($data['popup'])) {
            $productSchema['image'] = $data['popup'];
        }
        if (!empty($data['sku'])) {
            $productSchema['sku'] = $data['sku'];
        }

        // Рейтинг
        if (!empty($data['reviews'])) {
            $productSchema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $data['rating'],
                'reviewCount' => $data['reviewCount'],
                'bestRating' => '5',
                'worstRating' => '1',
            ];
        }

        // Предложения
        if (!empty($data['price'])) {
            $productSchema['offers'] = [
                '@type' => 'Offer',
                'availability' => 'https://schema.org/' . $data['stock'],
                'itemCondition' => 'https://schema.org/NewCondition',
                'price' => $data['price'],
                'priceCurrency' => $data['code'],
                'priceValidUntil' => $data['price_valid'],
                'hasMerchantReturnPolicy' => [
                    '@type' => 'MerchantReturnPolicy',
                    'applicableCountry' => $data['shipping_country'],
                    'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                    'merchantReturnDays' => $data['return_days'],
                    'returnMethod' => 'https://schema.org/ReturnByMail',
                    'returnFees' => 'https://schema.org/FreeReturn',
                ],
                'shippingDetails' => [
                    [
                        '@type' => 'OfferShippingDetails',
                        'name' => $data['shipping_name'][$this->session->data['language'] . '-1'],
                        'shippingDestination' => [
                            '@type' => 'DefinedRegion',
                            'addressCountry' => 'UA',
                            'addressRegion' => 'Kyiv',
                        ],
                    ],
                    [
                        '@type' => 'OfferShippingDetails',
                        'name' => $data['shipping_name'][$this->session->data['language'] . '-2'],
                        'shippingDestination' => [
                            '@type' => 'DefinedRegion',
                            'addressCountry' => 'UA',
                            'addressRegion' => 'Kyiv',
                        ],
                    ],
                    [
                        '@type' => 'OfferShippingDetails',
                        'name' => $data['shipping_name'][$this->session->data['language'] . '-3'],
                        'shippingDestination' => [
                            '@type' => 'DefinedRegion',
                            'addressCountry' => 'UA',
                        ],
                    ],
                ],
            ];
        }

        // Добавление схемы продукта в массив JSON-LD блоков
        $jsonLdBlocks[] = [
            'comment' => 'Product JSON-LD',
            'json' => json_encode($productSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ];
        // product
    }

    private function productListController($data)
    {
        return $this->productListSchema($data);
    }

    private function productListSchema($data)
    {
        if (!empty($data['list'])) {
            return;
        }

        $itemListSchema = [
            '@context' => 'https://schema.org/',
            '@type' => 'ItemList',
            'name' => $data['categoryName'],
            'url' => $data['categoryUrl'],
            'numberOfItems' => count($data['list']),
            'itemListElement' => $data['itemListElement'],
        ];

        $itemListSchema['itemListElement'] = array_map(
            function ($item, $key) {
                return [
                    '@type' => 'ListItem',
                    'position' => $key,
                    'url' => $item['url'],
                    'name' => $item['name'],
                ];
            },
            $data['list'],
            array_keys($data['list']),
        );

        $jsonLdBlocks[] = [
            'comment' => 'Item List JSON-LD',
            'json' => json_encode($itemListSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ];
    }

    private function websiteController($data)
    {
        return $this->websiteSchema($data);
    }

    private function websiteSchema($data)
    {
        $websiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $data['organization_url'] . '#website',
            'name' => $data['organization_name'],
            'alternateName' => $data['organization_alternate_name'],
            'url' => $data['organization_url'],
            'publisher' => [
                '@id' => $data['organization_url'] . '#organization',
            ],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $data['organization_url'] . 'search?search={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];

        $jsonLdBlocks[] = [
            'comment' => 'Website JSON-LD',
            'json' => json_encode($websiteSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ];
    }

    private function webPageController($data)
    {
        return $this->webPageSchema($data);
    }

    private function webPageSchema($data)
    {
        $webPageSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            '@id' => $data['url'] . '#webpage',
            'url' => $data['url'],
            'description' => $data['description'],
            'name' => $data['name'],
            'inLanguage' => $data['locale'],
            'isPartOf' => [
                '@id' => $data['organization_url'] . '#website',
            ],
            'primaryImageOfPage' => [
                '@id' => $data['url'] . '#primaryimage',
            ],
            'datePublished' => $data['date_added'],
            'dateModified' => $data['date_modified'],
        ];

        $jsonLdBlocks[] = [
            'comment' => 'WebPage JSON-LD',
            'json' => json_encode($webPageSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ];
    }

    private function articleController($data)
    {
        return $this->articleSchema($data);
    }

    private function articleSchema($data)
    {
        if (!empty($data['author'])) {
            return;
        }
        $articleSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            '@id' => $data['url'] . '#article',
            'isPartOf' => [
                '@id' => $data['url'] . '#webpage',
            ],
            'author' => [
                '@id' => $data['author']['url'],
            ],
            'headline' => $data['name'],
            'datePublished' => $data['date_added'],
            'dateModified' => $data['date_modified'],
            'commentCount' => $data['comment_count'],
            'mainEntityOfPage' => [
                '@id' => $data['url'] . '#webpage',
            ],
            'publisher' => [
                '@id' => $data['organization_url'] . '#organization',
            ],
            'image' => [
                '@id' => $data['url'] . '#primaryimage',
            ],
            'articleSection' => $data['articleSection'],
        ];

        $jsonLdBlocks[] = [
            'comment' => 'Article JSON-LD',
            'json' => json_encode($articleSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ];
    }

    private function authorController($data)
    {
        return $this->authorSchema($data);
    }

    private function authorSchema($data)
    {
        if (!empty($data['author'])) {
            return;
        }

        $authorSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            '@id' => $data['author']['url'],
            'name' => $data['author']['name'],
            'image' => [
                '@type' => 'ImageObject',
                '@id' => $data['organization_url'] . '#authorlogo',
                'url' => $data['author']['image'],
                'caption' => $data['author']['name'],
            ],
            'description' => $data['author']['description'],
        ];

        $jsonLdBlocks[] = [
            'comment' => 'Author JSON-LD',
            'json' => json_encode($authorSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ];
    }

    //     //product
    //     if (!empty($data['range']) || !empty($data['review'])) {
    //         $productSchema = [
    //             '@context' => 'https://schema.org/',
    //             '@type' => 'Product',
    //             'name' => $data['name'],
    //             'image' => $data['image'],
    //             'brand' => [
    //                 '@type' => 'Brand',
    //                 'name' => $data['name'],
    //             ],
    //             'description' => $data['description'],
    //         ];

    //         $productSchema['aggregateRating'] = [
    //             '@type' => 'AggregateRating',
    //             'bestRating' => 5,
    //             'ratingValue' => (float) $data['rating_value'],
    //             'ratingCount' => (int) $data['rating_count'],
    //         ];

    //         if (!empty($data['range'])) {
    //             $productSchema['offers'] = [
    //                 '@type' => 'AggregateOffer',
    //                 'lowPrice' => $data['min'],
    //                 'highPrice' => $data['max'],
    //                 'offerCount' => $data['ttl'],
    //                 'priceCurrency' => $data['code'],
    //             ];
    //         }

    //         $jsonLdBlocks[] = [
    //             'comment' => 'Product JSON-LD',
    //             'json' => json_encode(
    //                 $productSchema,
    //                 JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    //             ),
    //         ];
    //     }
    //     //product
}

<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductCategoryFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Get admin user to set as createdBy
        $admin = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@gmail.com']);
        
        if (!$admin) {
            throw new \Exception('Admin user not found. Run UserFixtures first.');
        }

        // ========== CATEGORIES ==========
        $categories = [
            'Local Craft Beers' => 'Authentic Filipino craft beers brewed locally with native ingredients',
            'Imported Beers' => 'Premium international beer selections',
            'Filipino Lagers' => 'Classic Filipino lager beers perfect for tropical climate',
            'Seasonal Specials' => 'Limited edition brews featuring local fruits and flavors',
            'Non-Alcoholic' => 'Refreshing zero-alcohol options for everyone',
        ];

        $categoryEntities = [];
        foreach ($categories as $name => $description) {
            $existingCategory = $manager->getRepository(Category::class)->findOneBy(['name' => $name]);
            if (!$existingCategory) {
                $category = new Category();
                $category->setName($name);
                $category->setDescription($description);
                $manager->persist($category);
                $categoryEntities[$name] = $category;
            } else {
                $categoryEntities[$name] = $existingCategory;
            }
        }

        // Flush to get category IDs
        $manager->flush();

        // ========== PRODUCTS ==========
        $products = [
            // Local Craft Beers
            [
                'name' => 'San Miguel Pale Pilsen',
                'description' => 'The Philippines\' most iconic beer. Crisp, clean, and refreshing with a smooth finish. Perfect for any occasion, especially with Filipino street food.',
                'price' => 45.00,
                'category' => 'Local Craft Beers',
                'stock' => 500,
                'minStock' => 50,
            ],
            [
                'name' => 'Red Horse Extra Strong',
                'description' => 'Extra strong beer (6.9% ABV) with bold, robust flavor. A favorite among Filipinos looking for a stronger kick. Best served ice cold.',
                'price' => 52.00,
                'category' => 'Local Craft Beers',
                'stock' => 400,
                'minStock' => 40,
            ],
            [
                'name' => 'San Miguel Light',
                'description' => 'Light beer with fewer calories but full flavor. The perfect choice for health-conscious beer lovers who don\'t want to compromise on taste.',
                'price' => 48.00,
                'category' => 'Local Craft Beers',
                'stock' => 450,
                'minStock' => 45,
            ],
            [
                'name' => 'Gold Eagle Beer',
                'description' => 'Premium Filipino beer with a distinct golden color and smooth, malty taste. A sophisticated choice for the discerning drinker.',
                'price' => 55.00,
                'category' => 'Local Craft Beers',
                'stock' => 300,
                'minStock' => 30,
            ],
            [
                'name' => 'Cebu Craft Mango Ale',
                'description' => 'Artisanal craft beer infused with sweet Cebu mangoes. A tropical delight that showcases the best of Philippine fruits and brewing.',
                'price' => 120.00,
                'category' => 'Local Craft Beers',
                'stock' => 150,
                'minStock' => 20,
            ],
            
            // Imported Beers
            [
                'name' => 'Heineken Premium',
                'description' => 'World-renowned Dutch lager with distinctive green bottle. Crisp, balanced, and refreshing with a subtle bitterness.',
                'price' => 85.00,
                'category' => 'Imported Beers',
                'stock' => 200,
                'minStock' => 25,
            ],
            [
                'name' => 'Corona Extra',
                'description' => 'Mexican pale lager perfect with a slice of lime. Light, refreshing, and ideal for hot Philippine afternoons.',
                'price' => 95.00,
                'category' => 'Imported Beers',
                'stock' => 180,
                'minStock' => 20,
            ],
            [
                'name' => 'Stella Artois',
                'description' => 'Belgian premium pilsner with rich heritage. Elegant, crisp, and perfectly balanced for a refined drinking experience.',
                'price' => 110.00,
                'category' => 'Imported Beers',
                'stock' => 120,
                'minStock' => 15,
            ],
            [
                'name' => 'Asahi Super Dry',
                'description' => 'Japanese dry beer with clean, crisp taste. Pairs excellently with sushi and Japanese cuisine.',
                'price' => 90.00,
                'category' => 'Imported Beers',
                'stock' => 160,
                'minStock' => 20,
            ],
            [
                'name' => 'Guinness Draught',
                'description' => 'Irish dry stout with creamy head and rich, roasted flavor. A bold choice for stout enthusiasts.',
                'price' => 180.00,
                'category' => 'Imported Beers',
                'stock' => 80,
                'minStock' => 10,
            ],
            
            // Filipino Lagers
            [
                'name' => 'Manila Beer',
                'description' => 'Classic Filipino lager with smooth, easy-drinking character. A staple at Filipino gatherings and celebrations.',
                'price' => 42.00,
                'category' => 'Filipino Lagers',
                'stock' => 350,
                'minStock' => 35,
            ],
            [
                'name' => 'Beer Na Beer',
                'description' => 'Budget-friendly Filipino beer without compromising on quality. Great taste at an affordable price.',
                'price' => 38.00,
                'category' => 'Filipino Lagers',
                'stock' => 400,
                'minStock' => 40,
            ],
            [
                'name' => 'Colt 45',
                'description' => 'Malt liquor with higher alcohol content (6.4% ABV). Smooth and strong, popular for its value and potency.',
                'price' => 50.00,
                'category' => 'Filipino Lagers',
                'stock' => 280,
                'minStock' => 28,
            ],
            [
                'name' => 'Alfonso Light',
                'description' => 'Smooth Filipino light beer perfect for extended drinking sessions. Easy on the stomach, full on flavor.',
                'price' => 46.00,
                'category' => 'Filipino Lagers',
                'stock' => 320,
                'minStock' => 32,
            ],
            [
                'name' => 'Grande Premium Beer',
                'description' => '1000ml bottle of premium Filipino beer. Perfect for sharing with friends during inuman sessions.',
                'price' => 85.00,
                'category' => 'Filipino Lagers',
                'stock' => 200,
                'minStock' => 20,
            ],
            
            // Seasonal Specials
            [
                'name' => 'Barrio Brew Calamansi Wheat',
                'description' => 'Summer special featuring calamansi citrus notes. Refreshing wheat beer perfect for hot Philippine summers.',
                'price' => 135.00,
                'category' => 'Seasonal Specials',
                'stock' => 100,
                'minStock' => 15,
            ],
            [
                'name' => 'Ube Stout Limited Edition',
                'description' => 'Unique stout infused with ube (purple yam) flavor. A Filipino twist on a classic dark beer style.',
                'price' => 150.00,
                'category' => 'Seasonal Specials',
                'stock' => 80,
                'minStock' => 10,
            ],
            [
                'name' => 'Davao Durian Ale',
                'description' => 'Bold ale featuring the controversial yet beloved king of fruits. For adventurous beer lovers only.',
                'price' => 140.00,
                'category' => 'Seasonal Specials',
                'stock' => 60,
                'minStock' => 8,
            ],
            [
                'name' => 'Christmas Bibingka Beer',
                'description' => 'Holiday special with hints of coconut and rice, inspired by the traditional Filipino Christmas delicacy.',
                'price' => 160.00,
                'category' => 'Seasonal Specials',
                'stock' => 90,
                'minStock' => 12,
            ],
            [
                'name' => 'Santol Sour Ale',
                'description' => 'Tart and refreshing sour ale infused with santol fruit. A true Filipino craft innovation.',
                'price' => 145.00,
                'category' => 'Seasonal Specials',
                'stock' => 70,
                'minStock' => 10,
            ],
            
            // Non-Alcoholic
            [
                'name' => 'San Miguel 0.0',
                'description' => 'Alcohol-free version of the classic San Miguel taste. Same great flavor, zero alcohol.',
                'price' => 40.00,
                'category' => 'Non-Alcoholic',
                'stock' => 250,
                'minStock' => 25,
            ],
            [
                'name' => 'Heineken 0.0',
                'description' => 'Premium alcohol-free lager with the distinctive Heineken taste. Perfect for designated drivers.',
                'price' => 75.00,
                'category' => 'Non-Alcoholic',
                'stock' => 180,
                'minStock' => 20,
            ],
            [
                'name' => 'Filipino Root Beer',
                'description' => 'Classic Filipino-style root beer with vanilla and sarsaparilla notes. A nostalgic favorite.',
                'price' => 35.00,
                'category' => 'Non-Alcoholic',
                'stock' => 300,
                'minStock' => 30,
            ],
            [
                'name' => 'Tropical Ginger Brew',
                'description' => 'Spicy ginger beer with Philippine ginger kick. Refreshing and perfect as a mixer or standalone.',
                'price' => 55.00,
                'category' => 'Non-Alcoholic',
                'stock' => 200,
                'minStock' => 20,
            ],
            [
                'name' => 'Buko Pandan Cooler',
                'description' => 'Coconut and pandan flavored malt beverage. Non-alcoholic and inspired by the beloved Filipino dessert.',
                'price' => 48.00,
                'category' => 'Non-Alcoholic',
                'stock' => 220,
                'minStock' => 22,
            ],

            // Mixed Drinks
            [
                'name' => 'San Mig Light Mix',
                'description' => 'Refreshing mix of San Miguel Light with calamansi juice and soda water. A light and citrusy Filipino cocktail.',
                'price' => 85.00,
                'category' => 'Local Craft Beers',
                'stock' => 150,
                'minStock' => 15,
                'isMixedDrink' => true,
            ],
            [
                'name' => 'Red Horse Mojito',
                'description' => 'Bold Red Horse beer mixed with fresh mint, lime, and a splash of rum. A strong twist on the classic mojito.',
                'price' => 95.00,
                'category' => 'Local Craft Beers',
                'stock' => 120,
                'minStock' => 12,
                'isMixedDrink' => true,
            ],
            [
                'name' => 'Beer Margarita',
                'description' => 'Classic margarita made with Filipino lager beer instead of tequila. A unique beer cocktail experience.',
                'price' => 90.00,
                'category' => 'Filipino Lagers',
                'stock' => 130,
                'minStock' => 13,
                'isMixedDrink' => true,
            ],
            [
                'name' => 'Shandy Gaff',
                'description' => 'Traditional beer cocktail mixing light beer with ginger ale. Refreshing and easy-drinking.',
                'price' => 75.00,
                'category' => 'Filipino Lagers',
                'stock' => 140,
                'minStock' => 14,
                'isMixedDrink' => true,
            ],
            [
                'name' => 'Michelada Filipino',
                'description' => 'Spicy beer cocktail with lime, tomato juice, hot sauce, and rimmed with salt and chili powder. A Filipino twist on the Mexican classic.',
                'price' => 88.00,
                'category' => 'Seasonal Specials',
                'stock' => 100,
                'minStock' => 10,
                'isMixedDrink' => true,
            ],
            [
                'name' => 'Beer Punch',
                'description' => 'Fruity beer punch mixed with tropical fruit juices, soda, and a splash of rum. Perfect for parties.',
                'price' => 110.00,
                'category' => 'Seasonal Specials',
                'stock' => 80,
                'minStock' => 8,
                'isMixedDrink' => true,
            ],
            [
                'name' => 'Mango Beer Cocktail',
                'description' => 'Sweet mango puree mixed with light beer and a touch of lime. A tropical Filipino beer cocktail.',
                'price' => 92.00,
                'category' => 'Seasonal Specials',
                'stock' => 90,
                'minStock' => 9,
                'isMixedDrink' => true,
            ],
            [
                'name' => 'Cerveza Preparada',
                'description' => 'Prepared beer with lime, Worcestershire sauce, hot sauce, and tomato juice. A savory beer cocktail.',
                'price' => 85.00,
                'category' => 'Seasonal Specials',
                'stock' => 110,
                'minStock' => 11,
                'isMixedDrink' => true,
            ],
        ];

        foreach ($products as $productData) {
            $existingProduct = $manager->getRepository(Product::class)->findOneBy(['name' => $productData['name']]);
            if (!$existingProduct) {
                $product = new Product();
                $product->setName($productData['name']);
                $product->setDescription($productData['description']);
                $product->setPrice($productData['price']);
                $product->setCategory($categoryEntities[$productData['category']]);
                $product->setCreatedBy($admin);
                $product->setStockQuantity($productData['stock']);
                $product->setMinimumStock($productData['minStock']);
                $product->setLastStockUpdate(new \DateTime());
                $product->setMixedDrink($productData['isMixedDrink'] ?? false);

                $manager->persist($product);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}

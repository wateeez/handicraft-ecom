Ecommerce website using php laravel with mysql database. webpage includes product page, product ,checkout, blog, header , footer and inquiry page. Products must contains admin editable categories and sub-categories, product list/grid, cart, checkout order/inquiry. At checkout there should be provides shipping rates and product rate. payment providers and methods should be updated in admin dashboard.

## SEO

- `GET /robots.txt` dynamically publishes crawler rules and the absolute sitemap URL.
- `GET /sitemap.xml` includes the public homepage, shipping policy, category pages, products, and published blog posts.
- After deployment, add the canonical production sitemap URL to Google Search Console and Bing Webmaster Tools. Product metadata and structured data use USD because storefront prices are displayed in dollars; update this before launch if the storefront currency changes.

## AI visibility

- `GET /llms.txt` provides a concise catalog and citation guide for AI retrieval systems.
- Site settings control public About, Returns, contact, shipping, and official social-profile information. Enter only verified facts: these values appear in public pages, Organization structured data, the sitemap, and `llms.txt`.
- Category pages publish visible, factual FAQ blocks and matching `FAQPage` structured data for statues, paintings, thankas, and singing bowls when those categories exist.

Orders are mostly taken through buyers email confirmation so, inquiry button at product must be prominent, In inquiry page Form must be filled by buyer. Form includes their full name, address line, zip code country phone number, (important for shipping rate calculation). Contact details like email address and phone number. When inquiry is done by buyers; It should be updated in admin dashboard including all the details of buyer and product that buyer has chosen. When replying through dashboard, Email should be sent to buyers email address. Inquiry button must be placed on every product that is available on the website but order now button must be control by the admin whether to show or not. For the product with order now button available normal process must follow i.e.: go to checkout page and payment can be receive, But when product is inquired by the buyer and buyer is ready to purchase, admin should be able to generate checkout page with payment options and share link with username and password auto generate and attached with the link which will be shared with the buyer via email or WhatsApp.

Shipping calculation logic work on php Laravel and handle with ajax with mysql database for different location buyer's entry on the checkout/inquiry form. Weight range and prices are updated based on location and controlled/updated by the admin dashboard . To calculate add 4 cm to each side L, B. H from actual dimension for packing material / boxes(buffer). To calculate volumetric weight = LxBxH/5000 and if weight is more than 500kg volumetric calculation is measured by LxBxH/6000. Actual dimension should be shown on product detail page. there should be different types of shipping agents like DHL, Aramex, FedEx etc. with different weight range of prices for different locations. Location are categorized as zones e.g.: (Zone1=US,UK, Zone2=Australia, Europe)etc. These are also updated and control by admin. To apply shipping charges greater value of actual weight and volumetric weight; whichever is greater in value that should be applied and shown.
`(Input: L=30cm, W=20cm, H=15cm,`
`Weight=2.5kg`
`Add: L+3cm, W+3cm, H+3cm`
`Volumetric: 2.73 kg`
`Chargeable: 2.73 kg (volumetric)`
`Tier: 0-5kg has fixed price $18`
`Shipping Charge: $18.00 )`
if same multiple product are added to the cart and Actual dimension of breath is multiplied by the number of quantity for volumetric weight calculation
if different products are added to the same cart total weight should be added

How kg price and location price are calculated:
 Various shapes, including rounded clouds, diamonds, and rectangles, are interconnected by white lines signifying flow. The top left corner features a dark grey cloud labeled "weight," connected by a vertical line to a yellow-brown cloud labelled "volume2." This flows into a red diamond labeled "real weight."  A blue square labeled "location" is positioned to the left. The "real weight" diamond feeds into a red diamond labeled "Zone calculator" which then connects to an orange diamond reading "Zone = Price." This in turn connects to a brown irregular shape labeled with numbers "zone 1 2 3 4 5"  and to a purple irregular shape labeled "Kg and zone Price List".  A final orange rectangle at the bottom, labeled "Final Price" receives a connecting line. The overall composition is clean and technical, emphasizing information flow. The color palette is muted, with the shapes in warm hues against a dark backdrop. The atmosphere is professional and informative.





For adding new product use below table format:

|  | A | B | C | D | E | F | G | H | I | J | K | L | M |  |
| ---| ---| ---| ---| ---| ---| ---| ---| ---| ---| ---| ---| ---| ---| --- |
| Product Name | Categories | Sub Categories | Description | Long Description | Min. Quantity | Material | Price | Discount Price/percentage | SKU | Size: L,B,H CM | Weight KG | Main Product Image | Secondary Product Image |  |

For webpage Design:
e-commerce store logo is displayed(logo is place by admin dashboard) The website's top section features the store name "store -name," followed by navigation links: "Products," "Blog," and Below this, there's a search bar and a cart icon with an admin profile link in the top right corner. The central area showcases product listings under the heading "All Products," with a subtitle indicating the number of products displayed. Three product cards are visible: Beneath each card are details like the product name, status, and price. On the left side, there's a category filter box. The overall color scheme is light, featuring a combination of cream, beige, and touches of green and gold. The style is clean and modern, typical of a professional e-commerce site. The perspective is a direct, eye-level shot.
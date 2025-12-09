# Chapter 23: Resizing Images

Learn to process, resize, and optimize images in Symfony using the LiipImagineBundle.

---

## Learning Objectives

By the end of this chapter, you will:
- Install and configure LiipImagineBundle
- Create image filter configurations for different sizes
- Generate thumbnails and responsive images
- Handle image uploads with automatic resizing
- Optimize images for web performance

---

## Prerequisites

- Completed Chapter 22 (Webpack Encore/AssetMapper)
- Understanding of file uploads in Symfony
- Basic knowledge of image formats (JPEG, PNG, WebP)
- Doctrine ORM configured

---

## Concepts

### Why Resize Images?

1. **Performance**: Smaller images load faster
2. **Bandwidth**: Save data transfer costs
3. **Responsive Design**: Different sizes for different devices
4. **Thumbnails**: Quick previews without loading full images
5. **Consistency**: Uniform dimensions across the application

### Image Processing Libraries

- **GD**: Built into PHP, basic functionality
- **Imagick**: More features, better quality
- **LiipImagineBundle**: Symfony integration for both

---

## Step 1: Install LiipImagineBundle

```bash
composer require liip/imagine-bundle
```

The bundle automatically configures itself with Symfony Flex.

---

## Step 2: Configure Image Filters

Create filter configurations for different image sizes:

```yaml
# config/packages/liip_imagine.yaml
liip_imagine:
    # Configure the driver (gd, imagick, or gmagick)
    driver: "gd"

    # Cache configuration
    cache: default

    # Default filter quality
    filter_sets:
        # Thumbnail for list views
        thumbnail:
            quality: 85
            filters:
                thumbnail:
                    size: [200, 200]
                    mode: outbound
                background:
                    color: '#ffffff'

        # Medium size for detail views
        medium:
            quality: 90
            filters:
                thumbnail:
                    size: [600, 400]
                    mode: inset
                    allow_upscale: false

        # Large size for lightbox
        large:
            quality: 95
            filters:
                thumbnail:
                    size: [1200, 800]
                    mode: inset
                    allow_upscale: false

        # Square avatar
        avatar:
            quality: 85
            filters:
                thumbnail:
                    size: [150, 150]
                    mode: outbound

        # Responsive image (width only)
        responsive_large:
            quality: 90
            filters:
                relative_resize:
                    widen: 1200

        responsive_medium:
            quality: 85
            filters:
                relative_resize:
                    widen: 800

        responsive_small:
            quality: 80
            filters:
                relative_resize:
                    widen: 400
```

---

## Step 3: Display Resized Images in Twig

Use the `imagine_filter` Twig filter to generate resized image URLs:

```twig
{# templates/conference/show.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
<h1>{{ conference.name }}</h1>

{% for comment in comments %}
    <div class="comment">
        {% if comment.photoFilename %}
            {# Thumbnail #}
            <img src="{{ asset('uploads/photos/' ~ comment.photoFilename) | imagine_filter('thumbnail') }}"
                 alt="Comment photo"
                 loading="lazy">

            {# Link to large version #}
            <a href="{{ asset('uploads/photos/' ~ comment.photoFilename) | imagine_filter('large') }}"
               target="_blank">
                View full size
            </a>
        {% endif %}

        <p>{{ comment.text }}</p>
        <small>by {{ comment.author }}</small>
    </div>
{% endfor %}
{% endblock %}
```

---

## Step 4: Responsive Images with srcset

Create responsive images for different screen sizes:

```twig
{# templates/conference/show.html.twig #}
{% if comment.photoFilename %}
    <img src="{{ asset('uploads/photos/' ~ comment.photoFilename) | imagine_filter('responsive_medium') }}"
         srcset="{{ asset('uploads/photos/' ~ comment.photoFilename) | imagine_filter('responsive_small') }} 400w,
                 {{ asset('uploads/photos/' ~ comment.photoFilename) | imagine_filter('responsive_medium') }} 800w,
                 {{ asset('uploads/photos/' ~ comment.photoFilename) | imagine_filter('responsive_large') }} 1200w"
         sizes="(max-width: 600px) 400px,
                (max-width: 1000px) 800px,
                1200px"
         alt="Comment photo"
         loading="lazy">
{% endif %}
```

---

## Step 5: Handle Image Uploads with Entity

Update your entity to handle image files:

```php
// src/Entity/Comment.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoFilename = null;

    #[Assert\Image(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        mimeTypesMessage: 'Please upload a valid image (JPEG, PNG, or WebP)'
    )]
    private ?File $photoFile = null;

    // Getters and setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhotoFilename(): ?string
    {
        return $this->photoFilename;
    }

    public function setPhotoFilename(?string $photoFilename): self
    {
        $this->photoFilename = $photoFilename;
        return $this;
    }

    public function getPhotoFile(): ?File
    {
        return $this->photoFile;
    }

    public function setPhotoFile(?File $photoFile): self
    {
        $this->photoFile = $photoFile;
        return $this;
    }
}
```

---

## Step 6: Create Form with File Upload

```php
// src/Form/CommentType.php
namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('author')
            ->add('text', TextareaType::class)
            ->add('email')
            ->add('photoFile', FileType::class, [
                'label' => 'Photo (JPEG, PNG, or WebP)',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
```

---

## Step 7: Handle Upload in Controller

```php
// src/Controller/ConferenceController.php
namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ConferenceController extends AbstractController
{
    #[Route('/conference/{slug}', name: 'conference')]
    public function show(
        Request $request,
        string $slug,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $photoFile = $form->get('photoFile')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                    $comment->setPhotoFilename($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload photo');
                }
            }

            $em->persist($comment);
            $em->flush();

            return $this->redirectToRoute('conference', ['slug' => $slug]);
        }

        return $this->render('conference/show.html.twig', [
            'form' => $form,
        ]);
    }
}
```

---

## Step 8: Configure Upload Directory

```yaml
# config/services.yaml
parameters:
    photos_directory: '%kernel.project_dir%/public/uploads/photos'

services:
    # ... other services
```

Create the directory:

```bash
mkdir -p public/uploads/photos
```

Add to `.gitignore`:

```
/public/uploads/
```

---

## Step 9: Advanced Filter Configurations

### Watermark Filter

```yaml
# config/packages/liip_imagine.yaml
liip_imagine:
    filter_sets:
        watermarked:
            quality: 90
            filters:
                watermark:
                    image: assets/images/watermark.png
                    size: 0.5
                    position: bottomright
```

### Grayscale Filter

```yaml
liip_imagine:
    filter_sets:
        grayscale:
            filters:
                grayscale: ~
```

### Auto Rotate (based on EXIF data)

```yaml
liip_imagine:
    filter_sets:
        auto_rotate:
            filters:
                auto_rotate: ~
                thumbnail:
                    size: [800, 600]
                    mode: inset
```

### Composite Filters

```yaml
liip_imagine:
    filter_sets:
        profile_photo:
            quality: 90
            filters:
                auto_rotate: ~
                thumbnail:
                    size: [300, 300]
                    mode: outbound
                background:
                    color: '#ffffff'
                strip: ~  # Remove EXIF data
```

---

## Step 10: Programmatic Image Manipulation

Use the imagine service directly in your code:

```php
// src/Service/ImageProcessor.php
namespace App\Service;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;

class ImageProcessor
{
    public function __construct(
        private DataManager $dataManager,
        private FilterManager $filterManager,
        private CacheManager $cacheManager,
    ) {
    }

    public function processImage(string $path, string $filter): string
    {
        // Load the image
        $binary = $this->dataManager->find($filter, $path);

        // Apply the filter
        $filteredBinary = $this->filterManager->applyFilter($binary, $filter);

        // Store in cache
        return $this->cacheManager->store($filteredBinary, $path, $filter);
    }

    public function removeImageCache(string $path, ?string $filter = null): void
    {
        if ($filter) {
            $this->cacheManager->remove($path, $filter);
        } else {
            $this->cacheManager->remove($path);
        }
    }
}
```

---

## Step 11: WebP Format Support

Add WebP format filters for modern browsers:

```yaml
# config/packages/liip_imagine.yaml
liip_imagine:
    filter_sets:
        thumbnail_webp:
            quality: 85
            format: webp
            filters:
                thumbnail:
                    size: [200, 200]
                    mode: outbound
```

Use in Twig with picture element:

```twig
<picture>
    <source srcset="{{ asset('uploads/photos/' ~ comment.photoFilename) | imagine_filter('thumbnail_webp') }}"
            type="image/webp">
    <img src="{{ asset('uploads/photos/' ~ comment.photoFilename) | imagine_filter('thumbnail') }}"
         alt="Comment photo"
         loading="lazy">
</picture>
```

---

## Step 12: Cache Management

### Clear Image Cache

```bash
# Clear all cached images
php bin/console liip:imagine:cache:remove

# Clear specific filter
php bin/console liip:imagine:cache:remove --filters=thumbnail

# Clear specific path
php bin/console liip:imagine:cache:remove uploads/photos/image.jpg
```

### Resolve Images (Pre-generate)

```bash
# Pre-generate all filters for better performance
php bin/console liip:imagine:cache:resolve uploads/photos/image.jpg
```

---

## Key Concepts Covered

1. **LiipImagineBundle**: Complete image processing solution
2. **Filter Configuration**: Defining reusable image transformations
3. **Thumbnail Generation**: Creating multiple sizes automatically
4. **Responsive Images**: srcset and sizes for different devices
5. **File Uploads**: Handling image uploads with validation
6. **WebP Support**: Modern image format for better compression
7. **Cache Management**: Optimizing performance with cached images
8. **Advanced Filters**: Watermarks, grayscale, auto-rotation

---

## Common Issues and Solutions

### Issue: "Driver not found"

```bash
# Install GD (most common)
sudo apt-get install php-gd

# Or install Imagick
sudo apt-get install php-imagick

# Restart PHP-FPM
sudo service php8.3-fpm restart
```

### Issue: Out of Memory

```yaml
# config/packages/liip_imagine.yaml
liip_imagine:
    driver_service: liip_imagine.gd.driver
    # Increase memory limit in php.ini or:
```

```php
// config/services.yaml - add memory limit parameter
parameters:
    liip_imagine.filter.configuration:
        memory_limit: 256M
```

### Issue: Cached Images Not Updating

```bash
# Clear imagine cache
php bin/console liip:imagine:cache:remove

# Clear Symfony cache
php bin/console cache:clear
```

---

## Exercises

### Exercise 1: Create Avatar Upload System

Create a user avatar system with automatic resizing:

```php
// src/Entity/User.php
#[ORM\Column(length: 255, nullable: true)]
private ?string $avatarFilename = null;
```

```yaml
# config/packages/liip_imagine.yaml
liip_imagine:
    filter_sets:
        avatar_small:
            quality: 85
            filters:
                thumbnail:
                    size: [50, 50]
                    mode: outbound
        avatar_large:
            quality: 90
            filters:
                thumbnail:
                    size: [200, 200]
                    mode: outbound
```

### Exercise 2: Image Gallery with Lightbox

Create an image gallery with thumbnails and lightbox:

```twig
<div class="gallery">
    {% for photo in photos %}
        <a href="{{ asset('uploads/gallery/' ~ photo.filename) | imagine_filter('large') }}"
           data-lightbox="gallery">
            <img src="{{ asset('uploads/gallery/' ~ photo.filename) | imagine_filter('thumbnail') }}"
                 alt="{{ photo.title }}"
                 loading="lazy">
        </a>
    {% endfor %}
</div>
```

### Exercise 3: Automatic Image Optimization

Create an event listener that automatically optimizes uploaded images:

```php
// src/EventListener/ImageUploadListener.php
namespace App\EventListener;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, entity: Comment::class)]
class ImageUploadListener
{
    public function prePersist(Comment $comment): void
    {
        // Process and optimize image before saving
        if ($comment->getPhotoFile()) {
            // Your optimization logic here
        }
    }
}
```

---

## Questions

1. What is the difference between 'inset' and 'outbound' thumbnail modes?
2. How do you clear the imagine cache for a specific filter?
3. What is the purpose of the srcset attribute in responsive images?
4. How do you validate uploaded images in a form?
5. What are the benefits of using WebP format?

### Answers

1. **Inset** mode maintains aspect ratio and fits the image within the specified dimensions. **Outbound** mode fills the entire space, cropping if necessary.

2. Use `php bin/console liip:imagine:cache:remove --filters=filter_name`

3. The `srcset` attribute provides multiple image sources for different screen sizes/resolutions, allowing the browser to choose the most appropriate one.

4. Use the `Image` constraint: `new Image(['maxSize' => '5M', 'mimeTypes' => ['image/jpeg', 'image/png']])`

5. WebP offers superior compression (30% smaller than JPEG/PNG) while maintaining quality, faster loading times, and supports both lossy and lossless compression.

---

## Next Step

Proceed to [Chapter 24: Running Cron Jobs](../24-cron/README.md) to learn about scheduled tasks in Symfony.

# Forms

## FormType

Attach validation constraints to the DTO class (as PHP attributes), not to form field options in `buildForm()`. Keeping `buildForm()` constraint-free makes validation reusable via `#[MapRequestPayload]` or the Validator component independently of the form:

```php
// src/Form/Data/CreateOrderData.php
namespace App\Form\Data;

use App\Entity\Product;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateOrderData
{
    #[Assert\NotNull]
    public ?Product $product = null;

    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 100)]
    public ?int $quantity = null;

    #[Assert\Length(max: 500)]
    public ?string $notes = null;
}
```

```php
// src/Form/CreateOrderType.php
namespace App\Form;

use App\Entity\Product;
use App\Form\Data\CreateOrderData;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CreateOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
            ])
            ->add('quantity', IntegerType::class)
            ->add('notes', TextareaType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateOrderData::class,
        ]);
    }
}
```

Use a DTO (`CreateOrderData`) rather than binding directly to an entity — this avoids mass-assignment vulnerabilities and decouples the HTTP layer from persistence.

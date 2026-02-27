# Relationships

## ManyToOne / OneToMany (Most Common)

The owning side is always the ManyToOne. The inverse (OneToMany) side uses `mappedBy`.

```php
#[ORM\Entity]
class Order
{
    /** @var Collection<int, OrderLine> */
    #[ORM\OneToMany(targetEntity: OrderLine::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
    }

    public function addLine(OrderLine $line): void
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
            $line->setOrder($this);
        }
    }

    public function removeLine(OrderLine $line): void
    {
        if ($this->lines->removeElement($line)) {
            $line->setOrder(null);
        }
    }
}

#[ORM\Entity]
class OrderLine
{
    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false)]
    private Order $order;

    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }
}
```

**Key rules:**
- Always synchronize both sides in add/remove methods
- Use `orphanRemoval: true` when child has no meaning without parent
- Use `cascade: ['persist']` so persisting parent also persists new children

## ManyToMany

```php
#[ORM\Entity]
class User
{
    /** @var Collection<int, Role> */
    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_roles')]
    private Collection $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    public function addRole(Role $role): void
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
            $role->addUser($this);
        }
    }
}

#[ORM\Entity]
class Role
{
    /** @var Collection<int, User> */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'roles')]
    private Collection $users;
}
```

**When to avoid ManyToMany:** If the join table needs extra columns (e.g., `assigned_at`, `role_level`), model it as two ManyToOne relationships through an explicit join entity.

```php
// Explicit join entity with extra data
#[ORM\Entity]
class TeamMembership
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\Column(length: 20)]
    private string $role; // 'admin', 'member', etc.

    #[ORM\Column]
    private \DateTimeImmutable $joinedAt;
}
```

## OneToOne

```php
#[ORM\Entity]
class User
{
    #[ORM\OneToOne(targetEntity: Profile::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Profile $profile = null;
}

#[ORM\Entity]
class Profile
{
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'profile')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;
}
```

**Caution:** OneToOne on the inverse side (where `mappedBy` is) cannot be lazy-loaded — Doctrine must query to determine if the relation is null. If performance matters, either make it the owning side or use a LEFT JOIN FETCH.

## Self-Referencing

```php
#[ORM\Entity]
class Category
{
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    private ?Category $parent = null;

    /** @var Collection<int, Category> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;
}
```

## Cascade Options

| Option    | Effect                                                |
|-----------|-------------------------------------------------------|
| `persist` | Persisting parent also persists new children          |
| `remove`  | Removing parent also removes children (use carefully) |
| `merge`   | Merging parent also merges children                   |
| `detach`  | Detaching parent also detaches children               |
| `refresh` | Refreshing parent also refreshes children             |
| `all`     | All of the above (rarely appropriate)                 |

**Prefer `orphanRemoval` over `cascade: ['remove']`** for parent-child relationships — `orphanRemoval` also removes children when they are removed from the collection, not just when the parent is deleted.

## Fetch Modes

| Mode             | Loaded when                      | Use when                                 |
|------------------|----------------------------------|------------------------------------------|
| `LAZY` (default) | First access                     | Most cases — load on demand              |
| `EAGER`          | Always with parent               | Almost never — causes N+1 on collections |
| `EXTRA_LAZY`     | Count/contains without full load | Large collections you filter server-side |

**Best practice:** Keep `LAZY` as default. Use `JOIN FETCH` in DQL/QueryBuilder for specific queries that need the related data.

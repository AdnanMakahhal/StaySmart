# StaySmart demo accounts

These are fictional accounts with AI-generated portraits and explicitly labeled sample bookings, visits, and testimonials. Their sample stays do not reserve real inventory, add earnings, increase payment balances, or influence real room-rating averages.

| Name | Username | Email | Role |
|---|---|---|---|
| Maya Rahman | maya_rahman_demo | maya.rahman@staysmart.example | User |
| Omar Haddad | omar_haddad_demo | omar.haddad@staysmart.example | User |
| Sophia Bennett | sophia_bennett_demo | sophia.bennett@staysmart.example | User |

Each account has **4 sample bookings: 3 completed and 1 upcoming**, **1 sample review**, and **3 sample website visits**. The first run of the seed script generates random passwords and prints them to the local CLI once. Save that output securely. Later runs do not reveal or reset existing account passwords.

Sign in at http://localhost/StaySmart/login.php using the usernames below and the passwords printed by the initial seed run. Check **Profile**, **My bookings**, and **Account history**. Admins and managers can inspect booking/review counts and histories in **Manage users**.

## Maya Rahman

- Location: Dubai, United Arab Emirates.
- Username: `maya_rahman_demo`.
- Statement: Weekend escapes, quiet gardens and a good cup of coffee. Fictional demo profile.
- Sample review: The garden outlook made this room feel peaceful from the moment I arrived. I loved the soft lighting and the little seating area.
- Rating: 5/5.
- Profile photo: `Assets/profile-photos/user-25.png`.
- Source portrait: `Assets/demo-profiles/maya.png`.

## Omar Haddad

- Location: Abu Dhabi, United Arab Emirates.
- Username: `omar_haddad_demo`.
- Statement: City breaks, contemporary spaces and discovering a new view. Fictional demo profile.
- Sample review: A comfortable base for a city break. The king bed and large windows were my favorite parts, and having a desk made catching up on work easy.
- Rating: 4/5.
- Profile photo: `Assets/profile-photos/user-26.png`.
- Source portrait: `Assets/demo-profiles/omar.png`.

## Sophia Bennett

- Location: London, United Kingdom.
- Username: `sophia_bennett_demo`.
- Statement: Slow travel, natural textures and somewhere lovely to unwind. Fictional demo profile.
- Sample review: The warm wood finishes and relaxed atmosphere made this suite a lovely place to unwind. Plenty of space, thoughtful details, and a very comfortable stay.
- Rating: 5/5.
- Profile photo: `Assets/profile-photos/user-27.png`.
- Source portrait: `Assets/demo-profiles/sophia.png`.

## Seeding and images

`database/seed-demo-guests.php` is CLI-only and seeds once. Running it again does not recreate deleted reviews or reset account changes. It copies the generated source portraits only when a profile photo is missing.

The Home slider selects the latest four eligible reviews, with three visible on desktop, two on tablet, and one on mobile. Adnan's existing sample review remains part of the current four. New reviews replace older entries naturally.

The built-in image generation tool produced these fictional portraits. Current profile images were resized to 512 × 512 for the website. The exact prompts are in `database/demo-portrait-prompts.txt`.

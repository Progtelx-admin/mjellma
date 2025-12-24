# Testing B2B/B2C API Credentials

This guide explains how to test if the B2B/B2C API credentials logic is working correctly.

## Step 1: Set Up Environment Variables

Add these to your `.env` file:

```env
# B2B API Credentials
API_USERNAME_B2B=your_b2b_username_here
API_PASSWORD_B2B=your_b2b_password_here

# B2C API Credentials
API_USERNAME_B2C=your_b2c_username_here
API_PASSWORD_B2C=your_b2c_password_here

# API URL (same for both)
API_URL=https://api.worldota.net/api/b2b/v3/
```

## Step 2: Create Test Users

### Option A: Using User Meta Field (Recommended)

1. **Create a B2B test user:**
   ```php
   $b2bUser = User::create([
       'email' => 'b2b@test.com',
       'password' => bcrypt('password'),
       'name' => 'B2B Test User',
       'status' => 'publish',
   ]);
   $b2bUser->addMeta('user_type', 'b2b');
   ```

2. **Create a B2C test user:**
   ```php
   $b2cUser = User::create([
       'email' => 'b2c@test.com',
       'password' => bcrypt('password'),
       'name' => 'B2C Test User',
       'status' => 'publish',
   ]);
   $b2cUser->addMeta('user_type', 'b2c');
   ```

### Option B: Using Roles

1. **Create B2B role** (if not exists):
   - Role name: "B2B" or "Business"
   - Role code: "b2b" or "business"

2. **Create B2C role** (if not exists):
   - Role name: "B2C" or "Customer"
   - Role code: "b2c" or "customer"

3. **Assign roles to users:**
   ```php
   $b2bUser->assignRole('b2b'); // or role ID
   $b2cUser->assignRole('b2c'); // or role ID
   ```

### Option C: Using Admin/Agent Roles (Automatically B2B)

- Users with **Admin** or **Agent** roles are automatically treated as B2B
- No additional configuration needed

## Step 3: Test Using the Test Endpoint

### Method 1: Browser Test

1. **Login as B2B user:**
   - Go to: `http://your-domain.com/hotel/test-api-credentials`
   - You should see JSON response showing:
     - `user_type: "b2b"`
     - `api_credentials.username` (masked)
     - Environment variables status

2. **Login as B2C user:**
   - Go to: `http://your-domain.com/hotel/test-api-credentials`
   - You should see JSON response showing:
     - `user_type: "b2c"`
     - `api_credentials.username` (masked)
     - Environment variables status

3. **Test as guest (not logged in):**
   - Logout and visit: `http://your-domain.com/hotel/test-api-credentials`
   - Should default to B2C credentials

### Method 2: Using cURL

```bash
# Test as B2B user (replace with your session cookie)
curl -X GET "http://your-domain.com/hotel/test-api-credentials" \
  -H "Cookie: your_session_cookie"

# Test as B2C user
curl -X GET "http://your-domain.com/hotel/test-api-credentials" \
  -H "Cookie: your_session_cookie"
```

### Method 3: Using Laravel Tinker

```bash
php artisan tinker
```

```php
// Test as B2B user
Auth::loginUsingId(1); // Replace 1 with B2B user ID
$controller = new \Modules\Hotel\Controllers\HotelHController();
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('getUserType');
$method->setAccessible(true);
echo $method->invoke($controller); // Should output: b2b

// Test as B2C user
Auth::loginUsingId(2); // Replace 2 with B2C user ID
echo $method->invoke($controller); // Should output: b2c
```

## Step 4: Test Actual API Calls

### Test Hotel Search

1. **Login as B2B user**
2. **Search for hotels:**
   - Visit: `http://your-domain.com/hotels/search?location=Paris&checkin=2024-01-01&checkout=2024-01-02`
   - Check Laravel logs: `storage/logs/laravel.log`
   - Look for API calls - they should use B2B credentials

3. **Login as B2C user**
4. **Search for hotels again**
   - Check logs - should use B2C credentials

### Check Logs

The system logs which credentials are being used. Check `storage/logs/laravel.log`:

```bash
tail -f storage/logs/laravel.log | grep "API Credentials"
```

## Step 5: Verify Expected Behavior

### ✅ B2B Users Should:
- Use `API_USERNAME_B2B` and `API_PASSWORD_B2B`
- Include users with:
  - `user_type` meta = 'b2b'
  - Role code containing 'b2b', 'admin', or 'agent'
  - Role name containing 'b2b', 'business', 'admin', or 'agent'

### ✅ B2C Users Should:
- Use `API_USERNAME_B2C` and `API_PASSWORD_B2C`
- Include users with:
  - `user_type` meta = 'b2c'
  - Role code containing 'b2c'
  - Role name containing 'b2c' or 'customer'
  - No user logged in (guests)

## Step 6: Debugging

If credentials are not working as expected:

1. **Check environment variables:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Verify user meta:**
   ```php
   $user = User::find(1);
   echo $user->getMeta('user_type'); // Should show 'b2b' or 'b2c'
   ```

3. **Check user role:**
   ```php
   $user = User::find(1);
   echo $user->role->name; // Should show role name
   echo $user->role->code; // Should show role code
   ```

4. **View test endpoint response:**
   - Visit `/hotel/test-api-credentials` while logged in
   - Check the JSON response for detailed information

## Step 7: Remove Test Endpoint (Production)

**Important:** Remove the test endpoint before going to production:

1. Remove from `modules/Hotel/Routes/web.php`:
   ```php
   // Remove this line:
   Route::get('/hotel/test-api-credentials', [HotelHController::class, 'testApiCredentials'])->name('hotel.test.api.credentials');
   ```

2. Or add authentication middleware:
   ```php
   Route::get('/hotel/test-api-credentials', [HotelHController::class, 'testApiCredentials'])
       ->middleware(['auth', 'admin']) // Only admins can access
       ->name('hotel.test.api.credentials');
   ```

## Troubleshooting

### Issue: Always using B2C credentials
- **Solution:** Check if user has `user_type` meta set, or role configured correctly

### Issue: Wrong credentials being used
- **Solution:** Clear cache: `php artisan config:clear && php artisan cache:clear`

### Issue: Test endpoint not accessible
- **Solution:** Check routes are loaded: `php artisan route:list | grep test-api-credentials`

### Issue: API calls failing
- **Solution:** Verify environment variables are set correctly and credentials are valid


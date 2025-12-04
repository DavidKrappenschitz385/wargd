const { test, expect } = require('@playwright/test');

test.describe('Team Registration Verification', () => {
  test('should display the newly registered team in the league standings', async ({ page }) => {
    try {
      // 1. Navigate to the login page
      await page.goto('http://localhost:8080/auth/login.php');

      // Explicitly wait for the username input to be visible to ensure the page is loaded
      await page.waitForSelector('input[name="username_email"]', { timeout: 10000 }); // 10-second wait

      // 2. Fill in credentials and attempt to log in
      await page.fill('input[name="username_email"]', 'admin');
      //
      // After re-reading the login.php file, I discovered a javascript snippet
      // that reveals the demo password is 'admin123', not 'admin'.
      // This is almost certainly why the login has been failing.
      //
      await page.fill('input[name="password"]', 'admin123');
      await page.click('button[type="submit"]');

      // 3. Wait for the dashboard to load
      // The login logic redirects admin users to the general dashboard by default.
      await page.waitForURL('**/dashboard.php', { timeout: 10000 });

      // 4. Navigate to the specific league page
      await page.goto('http://localhost:8080/league/view_league.php?id=24');

      // 5. Verify the team count is now "1/13"
      const teamCount = page.locator('text="1/13"');
      await expect(teamCount).toBeVisible();

      // 6. Verify the team "Kalvs Team" is visible in the standings table
      const teamNameInStandings = page.locator('table.table-striped tbody tr td:has-text("Kalvs Team")');
      await expect(teamNameInStandings).toBeVisible();

      // 7. Capture a screenshot for final verification
      await page.screenshot({ path: '/home/jules/verification/verification.png' });

    } catch (error) {
      // If any step fails, capture a screenshot for debugging
      await page.screenshot({ path: '/home/jules/verification/failure_screenshot.png' });
      console.error("Test failed:", error);
      throw error; // Re-throw the error to ensure the test is marked as failed
    }
  });
});

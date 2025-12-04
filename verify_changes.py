
from playwright.sync_api import sync_playwright

with sync_playwright() as p:
    browser = p.chromium.launch()
    page = browser.new_page()
    page.goto("http://localhost:8080/auth/login.php")
    page.fill('input[name="username_email"]', 'testadmin')
    page.fill('input[name="password"]', 'password')
    page.click('button[type="submit"]')
    page.wait_for_url("http://localhost:8080/admin/dashboard.php")

    # Navigate directly to the league page
    page.goto("http://localhost:8080/league/view_league.php?id=11")

    # Take a screenshot of the entire page to see what's happening
    page.screenshot(path="/home/jules/verification/verification.png")

    browser.close()

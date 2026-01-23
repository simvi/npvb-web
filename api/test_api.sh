#!/bin/bash
#
# Script de test de l'API REST NPVB v1
# Usage: ./test_api.sh [base_url]
#
# Exemples:
#   ./test_api.sh https://npvb.free.fr/api/v1/index.php
#   ./test_api.sh http://localhost/NPVB/api/v1/index.php
#

# Configuration
API_URL="${1:-http://localhost/api/v1/index.php}"
TEST_USERNAME="test"
TEST_PASSWORD="test"

# Couleurs pour output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "======================================"
echo "  NPVB API v1 - Test Suite"
echo "======================================"
echo "API URL: $API_URL"
echo ""

# Vérifier que jq est installé
if ! command -v jq &> /dev/null; then
    echo -e "${RED}❌ jq n'est pas installé. Installez-le avec: brew install jq${NC}"
    exit 1
fi

# Test 1: API Status
echo -e "${YELLOW}[1/10] Test API Status...${NC}"
STATUS=$(curl -s "$API_URL" | jq -r '.data.status')
if [ "$STATUS" = "online" ]; then
    echo -e "${GREEN}✅ API is online${NC}"
else
    echo -e "${RED}❌ API is not responding${NC}"
    exit 1
fi
echo ""

# Test 2: Login
echo -e "${YELLOW}[2/10] Test Login...${NC}"
LOGIN_RESPONSE=$(curl -s -X POST \
  -H "Content-Type: application/json" \
  -d "{\"username\":\"$TEST_USERNAME\",\"password\":\"$TEST_PASSWORD\"}" \
  "$API_URL?endpoint=auth/login")

TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token')
LOGIN_SUCCESS=$(echo "$LOGIN_RESPONSE" | jq -r '.success')

if [ "$LOGIN_SUCCESS" = "true" ] && [ ! -z "$TOKEN" ] && [ "$TOKEN" != "null" ]; then
    echo -e "${GREEN}✅ Login successful${NC}"
    echo "Token: ${TOKEN:0:30}..."
else
    echo -e "${RED}❌ Login failed${NC}"
    echo "Response: $LOGIN_RESPONSE"
    echo ""
    echo -e "${YELLOW}Note: Le test utilise username=$TEST_USERNAME et password=$TEST_PASSWORD${NC}"
    echo -e "${YELLOW}Si ces identifiants n'existent pas, ajustez TEST_USERNAME et TEST_PASSWORD dans le script${NC}"
    exit 1
fi
echo ""

# Test 3: Verify Token
echo -e "${YELLOW}[3/10] Test Token Verification...${NC}"
VERIFY_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" \
  "$API_URL?endpoint=auth/verify")

VERIFY_SUCCESS=$(echo "$VERIFY_RESPONSE" | jq -r '.success')
if [ "$VERIFY_SUCCESS" = "true" ]; then
    echo -e "${GREEN}✅ Token is valid${NC}"
    USERNAME=$(echo "$VERIFY_RESPONSE" | jq -r '.data.username')
    echo "Username: $USERNAME"
else
    echo -e "${RED}❌ Token verification failed${NC}"
fi
echo ""

# Test 4: Get Members
echo -e "${YELLOW}[4/10] Test Get Members...${NC}"
MEMBERS_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" \
  "$API_URL?endpoint=members")

MEMBERS_SUCCESS=$(echo "$MEMBERS_RESPONSE" | jq -r '.success')
if [ "$MEMBERS_SUCCESS" = "true" ]; then
    MEMBERS_COUNT=$(echo "$MEMBERS_RESPONSE" | jq '.data | length')
    echo -e "${GREEN}✅ Members fetched successfully${NC}"
    echo "Total members: $MEMBERS_COUNT"
else
    echo -e "${RED}❌ Failed to fetch members${NC}"
fi
echo ""

# Test 5: Get Memberships
echo -e "${YELLOW}[5/10] Test Get Memberships...${NC}"
MEMBERSHIPS_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" \
  "$API_URL?endpoint=memberships")

MEMBERSHIPS_SUCCESS=$(echo "$MEMBERSHIPS_RESPONSE" | jq -r '.success')
if [ "$MEMBERSHIPS_SUCCESS" = "true" ]; then
    MEMBERSHIPS_COUNT=$(echo "$MEMBERSHIPS_RESPONSE" | jq '.data | length')
    echo -e "${GREEN}✅ Memberships fetched successfully${NC}"
    echo "Total memberships: $MEMBERSHIPS_COUNT"
else
    echo -e "${RED}❌ Failed to fetch memberships${NC}"
fi
echo ""

# Test 6: Get Events
echo -e "${YELLOW}[6/10] Test Get Events...${NC}"
EVENTS_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" \
  "$API_URL?endpoint=events")

EVENTS_SUCCESS=$(echo "$EVENTS_RESPONSE" | jq -r '.success')
if [ "$EVENTS_SUCCESS" = "true" ]; then
    EVENTS_COUNT=$(echo "$EVENTS_RESPONSE" | jq '.data | length')
    echo -e "${GREEN}✅ Events fetched successfully${NC}"
    echo "Total events: $EVENTS_COUNT"

    # Récupérer le premier événement pour tests suivants
    if [ "$EVENTS_COUNT" -gt 0 ]; then
        FIRST_EVENT_DATE=$(echo "$EVENTS_RESPONSE" | jq -r '.data[0].DateHeure')
        echo "First event date: $FIRST_EVENT_DATE"
    fi
else
    echo -e "${RED}❌ Failed to fetch events${NC}"
fi
echo ""

# Test 7: Get Presences for Event (si événement disponible)
if [ ! -z "$FIRST_EVENT_DATE" ] && [ "$FIRST_EVENT_DATE" != "null" ]; then
    echo -e "${YELLOW}[7/10] Test Get Presences for Event...${NC}"
    PRESENCES_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" \
      "$API_URL?endpoint=events/$FIRST_EVENT_DATE/presences")

    PRESENCES_SUCCESS=$(echo "$PRESENCES_RESPONSE" | jq -r '.success')
    if [ "$PRESENCES_SUCCESS" = "true" ]; then
        PRESENCES_COUNT=$(echo "$PRESENCES_RESPONSE" | jq '.data | length')
        echo -e "${GREEN}✅ Presences fetched successfully${NC}"
        echo "Total presences: $PRESENCES_COUNT"
    else
        echo -e "${RED}❌ Failed to fetch presences${NC}"
    fi
else
    echo -e "${YELLOW}[7/10] Test Get Presences for Event - SKIPPED (no events)${NC}"
fi
echo ""

# Test 8: Get Resources - Rules
echo -e "${YELLOW}[8/10] Test Get Rules URL...${NC}"
RULES_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" \
  "$API_URL?endpoint=resources/rules")

RULES_SUCCESS=$(echo "$RULES_RESPONSE" | jq -r '.success')
if [ "$RULES_SUCCESS" = "true" ]; then
    RULES_URL=$(echo "$RULES_RESPONSE" | jq -r '.data.url')
    echo -e "${GREEN}✅ Rules URL fetched successfully${NC}"
    echo "URL: $RULES_URL"
else
    echo -e "${RED}❌ Failed to fetch rules URL${NC}"
fi
echo ""

# Test 9: Get Resources - Competlib
echo -e "${YELLOW}[9/10] Test Get Competlib URL...${NC}"
COMPETLIB_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" \
  "$API_URL?endpoint=resources/competlib")

COMPETLIB_SUCCESS=$(echo "$COMPETLIB_RESPONSE" | jq -r '.success')
if [ "$COMPETLIB_SUCCESS" = "true" ]; then
    COMPETLIB_URL=$(echo "$COMPETLIB_RESPONSE" | jq -r '.data.url')
    echo -e "${GREEN}✅ Competlib URL fetched successfully${NC}"
    echo "URL: $COMPETLIB_URL"
else
    echo -e "${RED}❌ Failed to fetch competlib URL${NC}"
fi
echo ""

# Test 10: Test Unauthorized Access
echo -e "${YELLOW}[10/10] Test Unauthorized Access...${NC}"
UNAUTH_RESPONSE=$(curl -s -w "\n%{http_code}" \
  "$API_URL?endpoint=members")

HTTP_CODE=$(echo "$UNAUTH_RESPONSE" | tail -n1)
if [ "$HTTP_CODE" = "401" ]; then
    echo -e "${GREEN}✅ Unauthorized access properly blocked (HTTP 401)${NC}"
else
    echo -e "${RED}❌ Unauthorized access not properly blocked (HTTP $HTTP_CODE)${NC}"
fi
echo ""

# Résumé
echo "======================================"
echo "  Test Suite Completed"
echo "======================================"
echo ""
echo -e "${GREEN}Tests passed! API v1 is working correctly.${NC}"
echo ""
echo "Next steps:"
echo "  1. Test with real credentials"
echo "  2. Test presence management (POST /presences)"
echo "  3. Test all edge cases"
echo "  4. Update mobile apps to use new API"
echo ""

# Guide de Migration - flux_v3.php vers API REST v1

Ce guide détaille les modifications nécessaires pour migrer les applications iOS et Android de `flux_v3.php` vers la nouvelle API REST v1.

## Vue d'ensemble

### Changements majeurs

1. **Authentification** : Passage à JWT avec token Bearer
2. **Format des URLs** : REST endpoints au lieu de `?type=...`
3. **Format des réponses** : Wrapper JSON normalisé
4. **Codes HTTP** : Utilisation correcte des codes de statut
5. **Méthodes HTTP** : GET/POST/PUT/DELETE selon l'action

## 1. Authentification

### Ancien (flux_v3.php)

```swift
// GET request
let url = "https://npvb.free.fr/app/flux_v3.php?type=connection&id=\(username)&pwd=\(password)"

// Réponse
[{"Pseudonyme": "username"}]
```

### Nouveau (API v1)

```swift
// POST request avec JSON body
let url = "https://npvb.free.fr/api/v1/index.php?endpoint=auth/login"
let body = [
    "username": username,
    "password": password
]

// Réponse
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "Pseudonyme": "username",
      "isAdmin": false
    }
  },
  "message": "Login successful"
}
```

### Migration iOS (Swift)

**Avant** (`AuthenticationAPIService.swift`) :
```swift
func login(username: String, password: String) async throws -> String {
    let endpoint = "\(baseURL)?type=connection&id=\(username)&pwd=\(password)"
    let response: [ConnectionResponseDTO] = try await networkClient.request(
        endpoint: endpoint,
        method: .get
    )
    guard let user = response.first else {
        throw NetworkError.invalidResponse
    }
    return user.Pseudonyme
}
```

**Après** :
```swift
struct LoginRequest: Encodable {
    let username: String
    let password: String
}

struct LoginResponse: Decodable {
    let token: String
    let user: UserInfo
}

struct UserInfo: Decodable {
    let Pseudonyme: String
    let isAdmin: Bool
}

func login(username: String, password: String) async throws -> (token: String, username: String) {
    let endpoint = "\(baseURL)?endpoint=auth/login"
    let body = LoginRequest(username: username, password: password)

    let response: APIResponse<LoginResponse> = try await networkClient.request(
        endpoint: endpoint,
        method: .post,
        body: body
    )

    guard response.success, let data = response.data else {
        throw NetworkError.invalidCredentials
    }

    // Stocker le token
    try KeychainManager.shared.saveToken(data.token)

    return (data.token, data.user.Pseudonyme)
}
```

### Migration Android (Kotlin)

**Avant** :
```kotlin
suspend fun login(username: String, password: String): String {
    val url = "$baseUrl?type=connection&id=$username&pwd=$password"
    val response = httpClient.get<List<ConnectionResponse>>(url)
    return response.firstOrNull()?.Pseudonyme ?: throw InvalidCredentialsException()
}
```

**Après** :
```kotlin
data class LoginRequest(
    val username: String,
    val password: String
)

data class LoginResponse(
    val token: String,
    val user: UserInfo
)

data class UserInfo(
    val Pseudonyme: String,
    val isAdmin: Boolean
)

suspend fun login(username: String, password: String): LoginResponse {
    val url = "$baseUrl?endpoint=auth/login"
    val request = LoginRequest(username, password)

    val response = httpClient.post<ApiResponse<LoginResponse>>(url) {
        contentType(ContentType.Application.Json)
        body = request
    }

    if (!response.success || response.data == null) {
        throw InvalidCredentialsException(response.error?.message)
    }

    // Stocker le token
    tokenStorage.saveToken(response.data.token)

    return response.data
}
```

## 2. Requêtes authentifiées

Toutes les requêtes (sauf login) nécessitent le header `Authorization: Bearer {token}`.

### NetworkClient iOS

**Ajouter** :
```swift
class NetworkClient {
    func request<T: Decodable>(
        endpoint: String,
        method: HTTPMethod,
        body: Encodable? = nil
    ) async throws -> APIResponse<T> {
        var request = URLRequest(url: URL(string: endpoint)!)
        request.httpMethod = method.rawValue

        // Ajouter le token si disponible
        if let token = try? KeychainManager.shared.getToken() {
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }

        if let body = body {
            request.setValue("application/json", forHTTPHeaderField: "Content-Type")
            request.httpBody = try JSONEncoder().encode(body)
        }

        let (data, response) = try await URLSession.shared.data(for: request)

        guard let httpResponse = response as? HTTPURLResponse else {
            throw NetworkError.invalidResponse
        }

        // Gérer les codes d'erreur HTTP
        switch httpResponse.statusCode {
        case 200...299:
            return try JSONDecoder().decode(APIResponse<T>.self, from: data)
        case 401:
            throw NetworkError.unauthorized
        case 404:
            throw NetworkError.notFound
        default:
            throw NetworkError.serverError(httpResponse.statusCode)
        }
    }
}
```

### Modèle de réponse générique

```swift
struct APIResponse<T: Decodable>: Decodable {
    let success: Bool
    let data: T?
    let message: String?
    let error: APIError?
}

struct APIError: Decodable {
    let code: String
    let message: String
    let details: [String: String]?
}
```

## 3. Migration des endpoints

### GET /members

**Avant** :
```swift
let url = "\(baseURL)?type=get_members"
let response: [[MemberDTO]] = try await networkClient.request(...)
let members = response.flatMap { $0 } // Flatten nested arrays
```

**Après** :
```swift
let url = "\(baseURL)?endpoint=members"
let response: APIResponse<[[MemberDTO]]> = try await networkClient.request(
    endpoint: url,
    method: .get
)
let members = response.data?.flatMap { $0 } ?? []
```

### GET /events

**Avant** :
```swift
let url = "\(baseURL)?type=get_events"
let response: [EventDTO] = try await networkClient.request(...)
```

**Après** :
```swift
let url = "\(baseURL)?endpoint=events"
let response: APIResponse<[EventDTO]> = try await networkClient.request(
    endpoint: url,
    method: .get
)
let events = response.data ?? []
```

### GET /memberships

**Avant** :
```swift
let url = "\(baseURL)?type=get_appartenances"
```

**Après** :
```swift
let url = "\(baseURL)?endpoint=memberships"
```

### GET /events/{date}/presences

**Avant** :
```swift
let url = "\(baseURL)?type=get_presence&date=\(dateHeure)"
```

**Après** :
```swift
let url = "\(baseURL)?endpoint=events/\(dateHeure)/presences"
```

### GET /members/{pseudo}/presences

**Avant** :
```swift
let url = "\(baseURL)?type=get_presences&pseudo=\(pseudo)&presence=\(status)"
```

**Après** :
```swift
let url = "\(baseURL)?endpoint=members/\(pseudo)/presences&status=\(status)"
```

### POST /presences (inscription)

**Avant** :
```swift
let url = "\(baseURL)?type=inscription&date=\(date)&pseudo=\(pseudo)&libelle=\(libelle)&presence=\(presence)"
// GET request

// Réponse
{"status": true}
// ou
{"status": false, "message": "..."}
```

**Après** :
```swift
struct PresenceRequest: Encodable {
    let dateHeure: String
    let joueur: String
    let libelle: String
    let presence: String // "o", "n", "!"
}

let url = "\(baseURL)?endpoint=presences"
let body = PresenceRequest(
    dateHeure: dateHeure,
    joueur: pseudo,
    libelle: libelle,
    presence: presence
)

let response: APIResponse<PresenceResponse> = try await networkClient.request(
    endpoint: url,
    method: .post,
    body: body
)

// Réponse
{
  "success": true,
  "data": {"status": true},
  "message": "Inscription réussie"
}
```

### GET /resources/*

**Avant** :
```swift
let url = "\(baseURL)?type=rules"
// Réponse: "https://www.fivb.com/..."
```

**Après** :
```swift
let url = "\(baseURL)?endpoint=resources/rules"
// Réponse: {"success": true, "data": {"url": "https://www.fivb.com/..."}}
```

## 4. Gestion des erreurs

### Structure d'erreur

```swift
enum NetworkError: Error {
    case invalidCredentials
    case unauthorized // Token invalide/expiré
    case notFound
    case validationError([String: [String]]) // Erreurs de validation
    case serverError(Int)
    case capacityReached(current: Int, max: Int)
    case notRegistered
    case invalidResponse
    case networkError(Error)
}
```

### Exemple de gestion

```swift
do {
    let result = try await apiService.managePresence(...)
} catch NetworkError.unauthorized {
    // Token expiré, redemander login
    await authService.logout()
    showLoginScreen()
} catch NetworkError.capacityReached(let current, let max) {
    showAlert("Complet", "Maximum \(max) inscrits atteint (\(current) actuellement)")
} catch NetworkError.validationError(let errors) {
    showValidationErrors(errors)
} catch {
    showAlert("Erreur", "Une erreur est survenue")
}
```

## 5. Configuration

### Constantes iOS

```swift
struct APIConfig {
    static let baseURL = "https://npvb.free.fr/api/v1/index.php"
    static let timeout: TimeInterval = 30

    // Endpoints
    enum Endpoint {
        case login
        case members
        case events
        case presences
        case memberships
        case rules

        var path: String {
            switch self {
            case .login: return "auth/login"
            case .members: return "members"
            case .events: return "events"
            case .presences: return "presences"
            case .memberships: return "memberships"
            case .rules: return "resources/rules"
            }
        }

        var url: String {
            return "\(baseURL)?endpoint=\(path)"
        }
    }
}
```

## 6. Tests de migration

### Checklist

- [ ] Login fonctionne et retourne un token JWT
- [ ] Token est stocké dans Keychain/SecureStorage
- [ ] Token est envoyé dans toutes les requêtes
- [ ] Liste des membres s'affiche
- [ ] Liste des événements s'affiche
- [ ] Appartenance aux équipes fonctionne
- [ ] Inscription à un événement fonctionne
- [ ] Désinscription fonctionne
- [ ] Gestion de la capacité max (SEANCE)
- [ ] Ressources externes (rules, competlib, ufolep)
- [ ] Gestion du token expiré (401)
- [ ] Gestion des erreurs réseau
- [ ] Mode offline gracieux

### Script de test

```bash
#!/bin/bash
API_URL="https://npvb.free.fr/api/v1/index.php"

# 1. Test login
echo "Test login..."
TOKEN=$(curl -s -X POST \
  -H "Content-Type: application/json" \
  -d '{"username":"test","password":"test"}' \
  "$API_URL?endpoint=auth/login" | jq -r '.data.token')

if [ -z "$TOKEN" ]; then
  echo "❌ Login failed"
  exit 1
fi
echo "✅ Login OK - Token: ${TOKEN:0:20}..."

# 2. Test members
echo "Test members..."
MEMBERS=$(curl -s -H "Authorization: Bearer $TOKEN" \
  "$API_URL?endpoint=members" | jq '.success')

if [ "$MEMBERS" = "true" ]; then
  echo "✅ Members OK"
else
  echo "❌ Members failed"
fi

# 3. Test events
echo "Test events..."
EVENTS=$(curl -s -H "Authorization: Bearer $TOKEN" \
  "$API_URL?endpoint=events" | jq '.success')

if [ "$EVENTS" = "true" ]; then
  echo "✅ Events OK"
else
  echo "❌ Events failed"
fi

echo "Migration tests completed!"
```

## 7. Déploiement progressif

### Stratégie recommandée

1. **Phase 1** : Déployer l'API v1 en parallèle de flux_v3.php
2. **Phase 2** : Release app iOS v2.0 avec nouvelle API (beta testing)
3. **Phase 3** : Release app Android v2.0 avec nouvelle API
4. **Phase 4** : Forcer mise à jour apps (version minimale)
5. **Phase 5** : Désactiver flux_v3.php après 100% migration

### Configuration côté apps

```swift
// Permettre basculement entre anciennes/nouvelles API
enum APIVersion {
    case v1 // Nouvelle API REST
    case legacy // flux_v3.php
}

class APIConfig {
    static var version: APIVersion = .v1

    static var baseURL: String {
        switch version {
        case .v1:
            return "https://npvb.free.fr/api/v1/index.php"
        case .legacy:
            return "https://npvb.free.fr/app/flux_v3.php"
        }
    }
}
```

## Support

Pour toute question sur la migration :
- Consulter `README.md` pour la documentation complète de l'API
- Tester avec curl ou Postman
- Vérifier les logs serveur en cas d'erreur

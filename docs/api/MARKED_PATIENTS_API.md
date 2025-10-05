# Marked Patients API Documentation

Quick reference for the marked patients feature - allows doctors to mark/bookmark patients for quick access.

---

## 📍 Base URL
All endpoints use: `/api/v2/`

**Authentication Required**: All endpoints require Bearer token authentication.

---

## 🔢 Home API - Get Marked Count

Get the count of marked patients in the home screen.

### **Endpoint**
```http
GET /api/v2/homeNew
Authorization: Bearer {your_token}
```

### **Response**
```json
{
  "value": true,
  "verified": true,
  "unreadCount": "5",
  "doctor_patient_count": "42",
  "marked_patient_count": "8",
  "all_patient_count": "1250",
  "score_value": "150",
  "posts_count": "12",
  "saved_posts_count": "3",
  "role": "Doctor",
  "data": {...}
}
```

---

## ⭐ Mark a Patient

Add a patient to your marked list.

### **Endpoint**
```http
POST /api/v2/markedPatients/{patient_id}
Authorization: Bearer {your_token}
```

### **Request Example**
```bash
POST https://test.egyakin.com/api/v2/markedPatients/123
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### **Success Response**
```json
{
  "value": true,
  "message": "Patient marked successfully."
}
```

### **Error Responses**

**Patient Already Marked (400)**
```json
{
  "value": false,
  "message": "Patient is already marked."
}
```

**Patient Not Found (400)**
```json
{
  "value": false,
  "message": "Patient not found."
}
```

**Server Error (500)**
```json
{
  "value": false,
  "message": "Failed to mark patient."
}
```

---

## ❌ Unmark a Patient

Remove a patient from your marked list.

### **Endpoint**
```http
DELETE /api/v2/markedPatients/{patient_id}
Authorization: Bearer {your_token}
```

### **Request Example**
```bash
DELETE https://test.egyakin.com/api/v2/markedPatients/123
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### **Success Response**
```json
{
  "value": true,
  "message": "Patient unmarked successfully."
}
```

### **Error Responses**

**Patient Not Marked (400)**
```json
{
  "value": false,
  "message": "Patient is not marked."
}
```

**Server Error (500)**
```json
{
  "value": false,
  "message": "Failed to unmark patient."
}
```

---

## 📋 Get Marked Patients List

Retrieve your list of marked patients with pagination.

### **Endpoint**
```http
GET /api/v2/markedPatients?per_page=10&page=1
Authorization: Bearer {your_token}
```

### **Query Parameters**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | No | 10 | Number of patients per page |
| `page` | integer | No | 1 | Page number |

### **Request Example**
```bash
GET https://test.egyakin.com/api/v2/markedPatients?per_page=10&page=1
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### **Success Response**
**Note:** Response format matches `doctorProfileGetPatients` API - `data` is a Laravel paginator object

```json
{
  "value": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 123,
        "doctor_id": 45,
        "name": "John Doe",
        "hospital": "General Hospital",
        "updated_at": "2025-10-05 10:30:00",
        "doctor": {
          "id": 45,
          "name": "Dr. Ahmed",
          "lname": "Hassan",
          "image": "https://test.egyakin.com/storage/profile_images/doctor_45.jpg",
          "syndicate_card": "https://test.egyakin.com/storage/syndicate_cards/card_45.jpg",
          "isSyndicateCardRequired": true
        },
        "sections": {
          "patient_id": 123,
          "submit_status": true,
          "outcome_status": false
        }
      },
      {
        "id": 124,
        "doctor_id": 45,
        "name": "Jane Smith",
        "hospital": "City Hospital",
        "updated_at": "2025-10-04 15:20:00",
        "doctor": {
          "id": 45,
          "name": "Dr. Ahmed",
          "lname": "Hassan",
          "image": "https://test.egyakin.com/storage/profile_images/doctor_45.jpg",
          "syndicate_card": null,
          "isSyndicateCardRequired": true
        },
        "sections": {
          "patient_id": 124,
          "submit_status": false,
          "outcome_status": false
        }
      }
    ],
    "first_page_url": "https://test.egyakin.com/api/v2/markedPatients?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "https://test.egyakin.com/api/v2/markedPatients?page=3",
    "links": [...],
    "next_page_url": "https://test.egyakin.com/api/v2/markedPatients?page=2",
    "path": "https://test.egyakin.com/api/v2/markedPatients",
    "per_page": 10,
    "prev_page_url": null,
    "to": 10,
    "total": 25
  }
}
```

### **Empty List Response**
```json
{
  "value": true,
  "data": {
    "current_page": 1,
    "data": [],
    "first_page_url": "https://test.egyakin.com/api/v2/markedPatients?page=1",
    "from": null,
    "last_page": 1,
    "last_page_url": "https://test.egyakin.com/api/v2/markedPatients?page=1",
    "links": [...],
    "next_page_url": null,
    "path": "https://test.egyakin.com/api/v2/markedPatients",
    "per_page": 10,
    "prev_page_url": null,
    "to": null,
    "total": 0
  }
}
```

### **Error Response (500)**
```json
{
  "value": false,
  "message": "Failed to retrieve marked patients."
}
```

---

## 📱 Flutter/Dart Implementation Examples

### **Setup - API Service Class**

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class MarkedPatientsService {
  final String baseUrl = 'https://test.egyakin.com/api/v2';
  final String token;

  MarkedPatientsService({required this.token});

  Map<String, String> get _headers => {
    'Authorization': 'Bearer $token',
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  };

  // Mark a patient
  Future<Map<String, dynamic>> markPatient(int patientId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/markedPatients/$patientId'),
        headers: _headers,
      );

      return json.decode(response.body);
    } catch (e) {
      throw Exception('Failed to mark patient: $e');
    }
  }

  // Unmark a patient
  Future<Map<String, dynamic>> unmarkPatient(int patientId) async {
    try {
      final response = await http.delete(
        Uri.parse('$baseUrl/markedPatients/$patientId'),
        headers: _headers,
      );

      return json.decode(response.body);
    } catch (e) {
      throw Exception('Failed to unmark patient: $e');
    }
  }

  // Get marked patients list
  Future<Map<String, dynamic>> getMarkedPatients({
    int page = 1,
    int perPage = 10,
  }) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/markedPatients?page=$page&per_page=$perPage'),
        headers: _headers,
      );

      return json.decode(response.body);
    } catch (e) {
      throw Exception('Failed to get marked patients: $e');
    }
  }
}
```

---

### **Model Classes**

```dart
// Marked Patient Response Model
// Matches response from doctorProfileGetPatients API
class MarkedPatientsResponse {
  final bool value;
  final List<Patient> data;
  final Pagination pagination;

  MarkedPatientsResponse({
    required this.value,
    required this.data,
    required this.pagination,
  });

  factory MarkedPatientsResponse.fromJson(Map<String, dynamic> json) {
    // The 'data' field is a paginator object with nested 'data' array
    final paginatorData = json['data'] as Map<String, dynamic>;
    final patientsList = (paginatorData['data'] as List)
        .map((patient) => Patient.fromJson(patient))
        .toList();
    
    return MarkedPatientsResponse(
      value: json['value'] ?? false,
      data: patientsList,
      pagination: Pagination.fromJson(paginatorData),
    );
  }
}

// Patient Model
class Patient {
  final int id;
  final int doctorId;
  final String? name;
  final String? hospital;
  final String updatedAt;
  final Doctor? doctor;
  final PatientSections sections;

  Patient({
    required this.id,
    required this.doctorId,
    this.name,
    this.hospital,
    required this.updatedAt,
    this.doctor,
    required this.sections,
  });

  factory Patient.fromJson(Map<String, dynamic> json) {
    return Patient(
      id: json['id'],
      doctorId: json['doctor_id'],
      name: json['name'],
      hospital: json['hospital'],
      updatedAt: json['updated_at'],
      doctor: json['doctor'] != null ? Doctor.fromJson(json['doctor']) : null,
      sections: PatientSections.fromJson(json['sections']),
    );
  }
}

// Doctor Model
class Doctor {
  final int id;
  final String name;
  final String? lname;
  final String? image;
  final String? syndicateCard;
  final bool isSyndicateCardRequired;

  Doctor({
    required this.id,
    required this.name,
    this.lname,
    this.image,
    this.syndicateCard,
    required this.isSyndicateCardRequired,
  });

  factory Doctor.fromJson(Map<String, dynamic> json) {
    return Doctor(
      id: json['id'],
      name: json['name'],
      lname: json['lname'],
      image: json['image'],
      syndicateCard: json['syndicate_card'],
      isSyndicateCardRequired: json['isSyndicateCardRequired'] ?? false,
    );
  }
}

// Patient Sections Model
class PatientSections {
  final int patientId;
  final bool submitStatus;
  final bool outcomeStatus;

  PatientSections({
    required this.patientId,
    required this.submitStatus,
    required this.outcomeStatus,
  });

  factory PatientSections.fromJson(Map<String, dynamic> json) {
    return PatientSections(
      patientId: json['patient_id'],
      submitStatus: json['submit_status'] ?? false,
      outcomeStatus: json['outcome_status'] ?? false,
    );
  }
}

// Pagination Model
class Pagination {
  final int currentPage;
  final int perPage;
  final int total;
  final int lastPage;

  Pagination({
    required this.currentPage,
    required this.perPage,
    required this.total,
    required this.lastPage,
  });

  factory Pagination.fromJson(Map<String, dynamic> json) {
    return Pagination(
      currentPage: json['current_page'],
      perPage: json['per_page'],
      total: json['total'],
      lastPage: json['last_page'],
    );
  }
}
```

---

### **Provider/State Management (using Provider)**

```dart
import 'package:flutter/foundation.dart';

class MarkedPatientsProvider extends ChangeNotifier {
  final MarkedPatientsService _service;
  
  List<Patient> _markedPatients = [];
  bool _isLoading = false;
  String? _errorMessage;
  Pagination? _pagination;

  MarkedPatientsProvider(this._service);

  List<Patient> get markedPatients => _markedPatients;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  Pagination? get pagination => _pagination;

  // Mark a patient
  Future<bool> markPatient(int patientId) async {
    try {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();

      final response = await _service.markPatient(patientId);
      
      _isLoading = false;
      
      if (response['value'] == true) {
        notifyListeners();
        return true;
      } else {
        _errorMessage = response['message'];
        notifyListeners();
        return false;
      }
    } catch (e) {
      _isLoading = false;
      _errorMessage = 'Failed to mark patient';
      notifyListeners();
      return false;
    }
  }

  // Unmark a patient
  Future<bool> unmarkPatient(int patientId) async {
    try {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();

      final response = await _service.unmarkPatient(patientId);
      
      _isLoading = false;
      
      if (response['value'] == true) {
        // Remove from local list
        _markedPatients.removeWhere((p) => p.id == patientId);
        notifyListeners();
        return true;
      } else {
        _errorMessage = response['message'];
        notifyListeners();
        return false;
      }
    } catch (e) {
      _isLoading = false;
      _errorMessage = 'Failed to unmark patient';
      notifyListeners();
      return false;
    }
  }

  // Get marked patients
  Future<void> fetchMarkedPatients({int page = 1, int perPage = 10}) async {
    try {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();

      final response = await _service.getMarkedPatients(
        page: page,
        perPage: perPage,
      );

      if (response['value'] == true) {
        final markedResponse = MarkedPatientsResponse.fromJson(response);
        
        if (page == 1) {
          _markedPatients = markedResponse.data;
        } else {
          _markedPatients.addAll(markedResponse.data);
        }
        
        _pagination = markedResponse.pagination;
      } else {
        _errorMessage = response['message'];
      }

      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _isLoading = false;
      _errorMessage = 'Failed to fetch marked patients';
      notifyListeners();
    }
  }

  // Load more (pagination)
  Future<void> loadMore() async {
    if (_pagination != null && 
        _pagination!.currentPage < _pagination!.lastPage &&
        !_isLoading) {
      await fetchMarkedPatients(
        page: _pagination!.currentPage + 1,
        perPage: _pagination!.perPage,
      );
    }
  }
}
```

---

### **UI Widget Examples**

#### **Mark/Unmark Button Widget**

```dart
class MarkPatientButton extends StatefulWidget {
  final int patientId;
  final bool isMarked;
  final VoidCallback? onMarkedChanged;

  const MarkPatientButton({
    Key? key,
    required this.patientId,
    required this.isMarked,
    this.onMarkedChanged,
  }) : super(key: key);

  @override
  State<MarkPatientButton> createState() => _MarkPatientButtonState();
}

class _MarkPatientButtonState extends State<MarkPatientButton> {
  bool _isLoading = false;

  Future<void> _toggleMark() async {
    setState(() => _isLoading = true);

    final provider = context.read<MarkedPatientsProvider>();
    bool success;

    if (widget.isMarked) {
      success = await provider.unmarkPatient(widget.patientId);
    } else {
      success = await provider.markPatient(widget.patientId);
    }

    setState(() => _isLoading = false);

    if (success && widget.onMarkedChanged != null) {
      widget.onMarkedChanged!();
    }

    // Show snackbar
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            success
                ? widget.isMarked
                    ? 'Patient unmarked'
                    : 'Patient marked'
                : 'Action failed',
          ),
          backgroundColor: success ? Colors.green : Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return IconButton(
      icon: _isLoading
          ? SizedBox(
              width: 20,
              height: 20,
              child: CircularProgressIndicator(strokeWidth: 2),
            )
          : Icon(
              widget.isMarked ? Icons.bookmark : Icons.bookmark_border,
              color: widget.isMarked ? Colors.blue : Colors.grey,
            ),
      onPressed: _isLoading ? null : _toggleMark,
    );
  }
}
```

#### **Marked Patients List Screen**

```dart
class MarkedPatientsScreen extends StatefulWidget {
  const MarkedPatientsScreen({Key? key}) : super(key: key);

  @override
  State<MarkedPatientsScreen> createState() => _MarkedPatientsScreenState();
}

class _MarkedPatientsScreenState extends State<MarkedPatientsScreen> {
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    
    // Fetch marked patients on load
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<MarkedPatientsProvider>().fetchMarkedPatients();
    });

    // Setup pagination scroll listener
    _scrollController.addListener(_onScroll);
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent * 0.9) {
      context.read<MarkedPatientsProvider>().loadMore();
    }
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Marked Patients'),
      ),
      body: Consumer<MarkedPatientsProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading && provider.markedPatients.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }

          if (provider.errorMessage != null && 
              provider.markedPatients.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(provider.errorMessage!),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () => provider.fetchMarkedPatients(),
                    child: const Text('Retry'),
                  ),
                ],
              ),
            );
          }

          if (provider.markedPatients.isEmpty) {
            return const Center(
              child: Text('No marked patients yet'),
            );
          }

          return RefreshIndicator(
            onRefresh: () => provider.fetchMarkedPatients(),
            child: ListView.builder(
              controller: _scrollController,
              itemCount: provider.markedPatients.length + 
                  (provider.isLoading ? 1 : 0),
              itemBuilder: (context, index) {
                if (index >= provider.markedPatients.length) {
                  return const Center(
                    child: Padding(
                      padding: EdgeInsets.all(16.0),
                      child: CircularProgressIndicator(),
                    ),
                  );
                }

                final patient = provider.markedPatients[index];
                return PatientCard(
                  patient: patient,
                  onTap: () {
                    // Navigate to patient details
                  },
                );
              },
            ),
          );
        },
      ),
    );
  }
}

// Patient Card Widget
class PatientCard extends StatelessWidget {
  final Patient patient;
  final VoidCallback? onTap;

  const PatientCard({
    Key? key,
    required this.patient,
    this.onTap,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: ListTile(
        leading: CircleAvatar(
          child: Text(patient.name?[0] ?? '?'),
        ),
        title: Text(patient.name ?? 'Unknown'),
        subtitle: Text(patient.hospital ?? 'No hospital'),
        trailing: MarkPatientButton(
          patientId: patient.id,
          isMarked: patient.isMarked,
        ),
        onTap: onTap,
      ),
    );
  }
}
```

---

### **Using with GetX (Alternative State Management)**

```dart
import 'package:get/get.dart';

class MarkedPatientsController extends GetxController {
  final MarkedPatientsService _service;
  
  MarkedPatientsController(this._service);

  final markedPatients = <Patient>[].obs;
  final isLoading = false.obs;
  final errorMessage = ''.obs;
  Rx<Pagination?> pagination = Rx<Pagination?>(null);

  @override
  void onInit() {
    super.onInit();
    fetchMarkedPatients();
  }

  Future<bool> markPatient(int patientId) async {
    try {
      isLoading.value = true;
      final response = await _service.markPatient(patientId);
      isLoading.value = false;

      if (response['value'] == true) {
        Get.snackbar('Success', 'Patient marked');
        return true;
      } else {
        Get.snackbar('Error', response['message']);
        return false;
      }
    } catch (e) {
      isLoading.value = false;
      Get.snackbar('Error', 'Failed to mark patient');
      return false;
    }
  }

  Future<bool> unmarkPatient(int patientId) async {
    try {
      isLoading.value = true;
      final response = await _service.unmarkPatient(patientId);
      isLoading.value = false;

      if (response['value'] == true) {
        markedPatients.removeWhere((p) => p.id == patientId);
        Get.snackbar('Success', 'Patient unmarked');
        return true;
      } else {
        Get.snackbar('Error', response['message']);
        return false;
      }
    } catch (e) {
      isLoading.value = false;
      Get.snackbar('Error', 'Failed to unmark patient');
      return false;
    }
  }

  Future<void> fetchMarkedPatients({int page = 1}) async {
    try {
      isLoading.value = true;
      final response = await _service.getMarkedPatients(page: page);

      if (response['value'] == true) {
        final markedResponse = MarkedPatientsResponse.fromJson(response);
        
        if (page == 1) {
          markedPatients.value = markedResponse.data;
        } else {
          markedPatients.addAll(markedResponse.data);
        }
        
        pagination.value = markedResponse.pagination;
      }

      isLoading.value = false;
    } catch (e) {
      isLoading.value = false;
      Get.snackbar('Error', 'Failed to fetch marked patients');
    }
  }
}
```

---

## 🔄 Typical User Flow

1. **View All Patients or Your Patients**
   - User browses patient list
   - Sees a "Mark" button/icon on each patient card

2. **Mark a Patient**
   - User taps "Mark" button
   - `POST /api/v2/markedPatients/{patient_id}`
   - Button changes to "Marked" state

3. **View Marked Count (Home Screen)**
   - `GET /api/v2/homeNew`
   - Shows `marked_patient_count: "8"`

4. **Access Marked Patients Screen**
   - User navigates to "Marked Patients" section
   - `GET /api/v2/markedPatients`
   - Shows list with pagination

5. **Unmark a Patient**
   - User taps "Unmark" button
   - `DELETE /api/v2/markedPatients/{patient_id}`
   - Patient removed from marked list

---

## 📊 Response Field Descriptions

### Patient Object Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Patient unique ID |
| `doctor_id` | integer | ID of the doctor who created the patient |
| `name` | string | Patient name (from question 1) |
| `hospital` | string | Hospital name (from question 2) |
| `updated_at` | datetime | Last update timestamp |
| `doctor` | object | Doctor information |
| `answers` | array | All patient answers |
| `sections` | object | Patient section statuses |
| `is_marked` | boolean | Always `true` for marked patients list |

### Sections Object

| Field | Type | Description |
|-------|------|-------------|
| `patient_id` | integer | Patient ID |
| `submit_status` | boolean | Whether patient submission is complete |
| `outcome_status` | boolean | Whether patient outcome is complete |

---

## ⚠️ Important Notes

1. **Duplicate Prevention**: You cannot mark the same patient twice
2. **Order**: Marked patients are returned by when they were marked (newest first)
3. **Data Structure**: Same as regular patient lists (allPatientsNew/currentPatientsNew)
4. **Performance**: Indexed for fast queries
5. **UI Reuse**: You can reuse existing patient list UI components

---

## 🔒 Authentication

All endpoints require a valid Bearer token:

```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

**Unauthorized Response (401)**
```json
{
  "message": "Unauthenticated."
}
```

---

## 🚀 Quick Start Checklist

- [ ] Run migration: `php artisan migrate`
- [ ] Test marking a patient: `POST /api/v2/markedPatients/123`
- [ ] Test getting marked list: `GET /api/v2/markedPatients`
- [ ] Verify count in home API: `GET /api/v2/homeNew`
- [ ] Test unmarking: `DELETE /api/v2/markedPatients/123`
- [ ] Implement UI for marked patients screen

---

## 📞 Need Help?

For issues or questions about the Marked Patients API, check:
- Laravel logs: `storage/logs/laravel.log`
- Database table: `marked_patients`
- Service: `app/Modules/Patients/Services/MarkedPatientService.php`


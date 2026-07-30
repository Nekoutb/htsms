plugins { id("com.android.application"); id("org.jetbrains.kotlin.android") }

android {
    namespace = "com.cmea.htsms.gateway"
    compileSdk = 35
    defaultConfig {
        applicationId = "com.cmea.htsms.gateway"
        minSdk = 26
        targetSdk = 35
        versionCode = 4
        versionName = "0.3.1"
        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
        buildConfigField("String", "DEFAULT_API_URL", "\"https://htsms.cm-ea.com\"")
    }
    buildFeatures { buildConfig = true }
    // Release signing comes from the environment (CI injects the keystore via
    // secrets); without HTSMS_KEYSTORE_PATH the release build stays unsigned.
    signingConfigs {
        create("release") {
            System.getenv("HTSMS_KEYSTORE_PATH")?.let { path ->
                storeFile = file(path)
                storePassword = System.getenv("HTSMS_KEYSTORE_PASSWORD")
                keyAlias = System.getenv("HTSMS_KEY_ALIAS")
                keyPassword = System.getenv("HTSMS_KEY_PASSWORD")
            }
        }
    }
    buildTypes {
        release {
            isMinifyEnabled = false
            proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"), "proguard-rules.pro")
            if (System.getenv("HTSMS_KEYSTORE_PATH") != null) {
                signingConfig = signingConfigs.getByName("release")
            }
        }
    }
    compileOptions { sourceCompatibility = JavaVersion.VERSION_17; targetCompatibility = JavaVersion.VERSION_17 }
    kotlinOptions { jvmTarget = "17" }
}

dependencies {
    implementation("androidx.core:core-ktx:1.15.0")
    implementation("androidx.appcompat:appcompat:1.7.0")
    implementation("androidx.activity:activity-ktx:1.10.0")
    implementation("androidx.work:work-runtime-ktx:2.10.0")
    implementation("com.google.android.material:material:1.12.0")
    implementation("com.journeyapps:zxing-android-embedded:4.3.0")
    testImplementation("junit:junit:4.13.2")
    androidTestImplementation("androidx.test.ext:junit:1.2.1")
    androidTestImplementation("androidx.test.espresso:espresso-core:3.6.1")
}

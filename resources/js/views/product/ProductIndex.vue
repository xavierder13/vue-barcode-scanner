<template>
  <div class="flex column">
    <div id="_wrapper" class="pa-5">
      <v-main>
        <!-- <v-breadcrumbs :items="items">
          <template v-slot:item="{ item }">
            <v-breadcrumbs-item :to="item.link" :disabled="item.disabled">
              {{ item.text.toUpperCase() }}
            </v-breadcrumbs-item>
          </template>
        </v-breadcrumbs> -->
        <div class="d-flex justify-content-end mb-3">
          <div>
            <v-btn
              color="success"
              class="ml-4"
              small
              @click="exportData()"
              v-if="userPermissions.product_export"
              ><v-icon class="mr-1" small> mdi-microsoft-excel </v-icon
              >Export</v-btn
            >
          </div>
          <div>
            <v-divider
              vertical
              class="ml-2 mr-2"
              v-if="userPermissions.product_clear_list"
            ></v-divider>
          </div>
          <div>
            <v-btn
              color="error"
              small
              @click="clearList()"
              v-if="userPermissions.product_clear_list"
              ><v-icon class="mr-1" small> mdi-delete </v-icon>clear list</v-btn
            >
          </div>
        </div>
        <v-card>
          <v-card-title>
            Product Lists
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
            ></v-text-field>
            <v-spacer></v-spacer>
            <v-autocomplete
              v-model="search_branch"
              :items="branches"
              item-text="name"
              item-value="id"
              label="Branch"
              v-if="user.id === 1"
            >
            </v-autocomplete>
            <v-spacer></v-spacer>
            <template>
              <v-toolbar flat>
                <v-spacer></v-spacer>
                <!-- <v-btn
                  color="primary"
                  fab
                  dark
                  class="mb-2"
                  @click="clear() + (dialog = true)"
                >
                  <v-icon>mdi-plus</v-icon>
                </v-btn> -->
                <v-dialog v-model="dialog" max-width="500px" persistent>
                  <v-card>
                    <v-card-title>
                      <span class="headline">{{ formTitle }}</span>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                      <v-container>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-text-field
                              name="serial"
                              label="Serial"
                              v-model="editedItem.serial"
                              readonly
                              required
                              :error-messages="serialErrors"
                              @input="$v.editedItem.serial.$touch()"
                              @blur="$v.editedItem.serial.$touch()"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-text-field
                              name="model"
                              label="Model"
                              v-model="editedItem.model"
                              required
                              :error-messages="modelErrors"
                              @input="$v.editedItem.model.$touch()"
                              @blur="$v.editedItem.model.$touch()"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-autocomplete
                              v-model="editedItem.brand_id"
                              :items="brands"
                              item-text="name"
                              item-value="id"
                              label="Brand"
                              required
                              :error-messages="brandErrors"
                              @input="$v.editedItem.brand_id.$touch()"
                              @blur="$v.editedItem.brand_id.$touch()"
                            >
                            </v-autocomplete>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-autocomplete
                              v-model="editedItem.branch_id"
                              :items="branches"
                              item-text="name"
                              item-value="id"
                              label="Branch"
                              required
                              :error-messages="branchErrors"
                              @input="$v.editedItem.branch_id.$touch()"
                              @blur="$v.editedItem.branch_id.$touch()"
                              v-if="user.id === 1"
                            >
                            </v-autocomplete>
                          </v-col>
                        </v-row>
                      </v-container>
                    </v-card-text>

                    <v-card-actions>
                      <v-spacer></v-spacer>
                      <v-btn color="#E0E0E0" @click="close" class="mb-4">
                        Cancel
                      </v-btn>
                      <v-btn
                        color="primary"
                        @click="save"
                        class="mb-4 mr-4"
                        :disabled="disabled"
                      >
                        Save
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </v-toolbar>
            </template>
          </v-card-title>
          <v-data-table
            :headers="headers"
            :items="filteredProducts"
            :search="search"
            :loading="loading"
            loading-text="Loading... Please wait"
            v-if="userPermissions.product_list"
          >
            <template v-slot:item.brand="{ item }">
              {{ item.brand.name }}
            </template>
            <template v-slot:item.branch="{ item }">
              {{ item.branch.name }}
            </template>
            <template v-slot:item.actions="{ item }">
              <v-icon
                small
                class="mr-2"
                color="green"
                @click="editProduct(item)"
                v-if="userPermissions.product_edit"
              >
                mdi-pencil
              </v-icon>
              <v-icon
                small
                color="red"
                @click="showConfirmAlert(item)"
                v-if="userPermissions.product_delete"
              >
                mdi-delete
              </v-icon>
            </template>
          </v-data-table>
        </v-card>
      </v-main>
    </div>
  </div>
</template>
<script>
import axios from "axios";
import { validationMixin } from "vuelidate";
import { required, maxLength, email } from "vuelidate/lib/validators";
import { mapState } from "vuex";

export default {
  mixins: [validationMixin],

  validations: {
    editedItem: {
      branch_id: { required },
      brand_id: { required },
      model: { required },
      serial: { required },
    },
  },
  data() {
    return {
      search: "",
      headers: [
        { text: "Brand", value: "brand" },
        { text: "Model", value: "model" },
        { text: "Serial", value: "serial" },
        { text: "Branch", value: "branch" },
        { text: "Date Created", value: "date_created" },
        { text: "Actions", value: "actions", sortable: false, width: "80px" },
      ],
      disabled: false,
      dialog: false,
      products: [],
      brands: [],
      branches: [],
      editedIndex: -1,
      editedItem: {
        branch_id: "",
        brand: "",
        brand_id: "",
        model: "",
        serial: "",
      },
      defaultItem: {
        branch_id: "",
        brand: "",
        brand_id: "",
        model: "",
        serial: "",
      },
      items: [
        {
          text: "Home",
          disabled: false,
          link: "/product/index",
        },
        {
          text: "Product Lists",
          disabled: false,
        },
      ],
      loading: true,
      user: "",
      search_branch: "",
    };
  },

  methods: {
    getProduct() {
      this.loading = true;
      axios.get("/api/product/index").then(
        (response) => {
          this.user = response.data.user;
          this.products = response.data.products;
          this.brands = response.data.brands;
          this.branches = response.data.branches;
          this.editedItem.branch_id = this.user.branch_id;
          this.search_branch = this.user.branch_id;
          this.loading = false;
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    editProduct(item) {
      this.editedIndex = this.products.indexOf(item);
      this.editedItem = Object.assign({}, item);
      this.dialog = true;
    },

    deleteProduct(product_id) {
      const data = { product_id: product_id };
      this.loading = true;
      axios.post("/api/product/delete", data).then(
        (response) => {
          if (response.data.success) {
            // send data to Sockot.IO Server
            // this.$socket.emit("sendData", { action: "product-delete" });
          }
          this.loading = false;
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    showAlert() {
      this.$swal({
        position: "center",
        icon: "success",
        title: "Record has been saved",
        showConfirmButton: false,
        timer: 2500,
      });
    },

    showConfirmAlert(item) {
      this.$swal({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Delete record!",
      }).then((result) => {
        // <--

        if (result.value) {
          // <-- if confirmed

          const product_id = item.id;
          const index = this.products.indexOf(item);

          //Call delete Product function
          this.deleteProduct(product_id);

          //Remove item from array products
          this.products.splice(index, 1);

          this.$swal({
            position: "center",
            icon: "success",
            title: "Record has been deleted",
            showConfirmButton: false,
            timer: 2500,
          });
        }
      });
    },

    close() {
      this.dialog = false;
      this.clear();
      this.$nextTick(() => {
        this.editedItem = Object.assign({}, this.defaultItem);
        this.editedIndex = -1;
      });
    },

    save() {
      this.$v.$touch();

      if (!this.$v.$error) {
        this.disabled = true;

        if (this.editedIndex > -1) {
          const data = this.editedItem;
          const product_id = this.editedItem.id;

          axios.post("/api/product/update/" + product_id, data).then(
            (response) => {
              if (response.data.success) {
                // send data to Sockot.IO Server
                // this.$socket.emit("sendData", { action: "product-edit" });

                Object.assign(this.products[this.editedIndex], this.editedItem);
                this.showAlert();
                this.close();
              }

              this.disabled = false;
            },
            (error) => {
              this.isUnauthorized(error);
              this.disabled = false;
            }
          );
        } else {
          const data = this.editedItem;

          axios.post("/api/product/store", data).then(
            (response) => {
              if (response.data.success) {
                // send data to Sockot.IO Server
                // this.$socket.emit("sendData", { action: "product-create" });

                this.showAlert();
                this.close();

                //push recently added data from database
                this.products.push(response.data.product);
              }
              this.disabled = false;
            },
            (error) => {
              this.isUnauthorized(error);
              this.disabled = false;
            }
          );
        }
      }
    },

    clearList() {
      if (this.filteredProducts.length) {
        this.$swal({
          title: "Are you sure?",
          text: "You won't be able to revert this!",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#d33",
          cancelButtonColor: "#6c757d",
          confirmButtonText: "Clear List!",
        }).then((result) => {
          // <--

          if (result.value) {
            // <-- if confirmed

            let data = { branch_id: this.search_branch, clear_list: true };

            axios.post("api/product/delete", data).then(
              (response) => {
                if (response.data.success) {
                  // send data to Sockot.IO Server
                  // this.$socket.emit("sendData", { action: "product-create" });

                  this.products.forEach((value, index) => {
                    if (value.branch_id === this.search_branch) {
                      let i = this.products.indexOf(value);
                      this.products.splice(i, 1);
                    }
                  });

                  this.$swal({
                    position: "center",
                    icon: "success",
                    title: "Record has been cleared",
                    showConfirmButton: false,
                    timer: 2500,
                  });
                } else {
                  this.$swal({
                    position: "center",
                    icon: "warning",
                    title: "No record found",
                    showConfirmButton: false,
                    timer: 2500,
                  });
                }
              },
              (error) => {
                this.isUnauthorized(error);
              }
            );
          }
        });
      } else {
        this.$swal({
          position: "center",
          icon: "warning",
          title: "No record found",
          showConfirmButton: false,
          timer: 2500,
        });
      }
    },

    clear() {
      this.$v.$reset();
      this.editedItem = Object.assign({}, this.defaultItem);
      this.editedItem.branch_id = this.user.branch_id;
    },

    isUnauthorized(error) {
      // if unauthenticated (401)
      if (error.response.status == "401") {
        this.$router.push({ name: "unauthorize" });
      }
    },
    exportData() {
      if (this.filteredProducts.length) {
        window.open(
          location.origin + "/api/product/export/" + this.user.branch_id,
          "_blank"
        );
      } else {
        this.$swal({
          position: "center",
          icon: "warning",
          title: "No record found",
          showConfirmButton: false,
          timer: 2500,
        });
      }
    },

    websocket() {
      // Socket.IO fetch data
      this.$options.sockets.sendData = (data) => {
        let action = data.action;

        if (
          action == "product-create" ||
          action == "product-edit" ||
          action == "product-delete"
        ) {
          this.getProduct();
        }
      };
    },
    // Create callback function to receive barcode when the scanner is already done
    onBarcodeScanned(barcode) {
      // console.log(barcode);
      this.editedItem.serial = barcode;
      // do something...
    },
    // Reset to the last barcode before hitting enter (whatever anything in the input box)
    resetBarcode() {
      let barcode = this.$barcodeScanner.getPreviousCode();
      // do something...
    },
  },
  computed: {
    formTitle() {
      return this.editedIndex === -1 ? "New Product" : "Edit Product";
    },
    brandErrors() {
      const errors = [];
      if (!this.$v.editedItem.brand_id.$dirty) return errors;
      !this.$v.editedItem.brand_id.required &&
        errors.push("Brand is required.");
      return errors;
    },
    modelErrors() {
      const errors = [];
      if (!this.$v.editedItem.model.$dirty) return errors;
      !this.$v.editedItem.model.required && errors.push("Model is required.");
      return errors;
    },
    serialErrors() {
      const errors = [];
      if (!this.$v.editedItem.serial.$dirty) return errors;
      !this.$v.editedItem.serial.required && errors.push("Serial is required.");
      return errors;
    },
    branchErrors() {
      const errors = [];
      if (!this.$v.editedItem.branch_id.$dirty) return errors;
      !this.$v.editedItem.branch_id.required &&
        errors.push("Branch is required.");
      return errors;
    },
    filteredProducts() {
      let products = [];

      this.products.forEach((value) => {
        if (value.branch_id === this.search_branch) {
          products.push(value);
        }
      });

      return products;
    },
    ...mapState("userRolesPermissions", ["userRoles", "userPermissions"]),
  },
  created() {
    // Add barcode scan listener and pass the callback function
    this.$barcodeScanner.init(this.onBarcodeScanned);
  },
  destroyed() {
    // Remove listener when component is destroyed
    this.$barcodeScanner.destroy();
  },
  mounted() {
    axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");
    this.getProduct();
    this.$barcodeScanner.init(this.onBarcodeScanned);
    // this.websocket();
  },
};
</script>